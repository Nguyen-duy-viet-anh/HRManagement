<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LunchOrder;
use App\Http\Controllers\VnpayController;
use App\Services\OnepayService;
use App\Models\VnpayTransactionLog;
use App\Models\OnepayTransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LunchOrderController extends Controller
{
    // POST /api/lunch-orders/list
    public function index(Request $request)
    {
        $query = LunchOrder::with('user')->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        return apiSuccess(
            $query->paginate(20),
            'Danh sách đơn đặt cơm'
        );
    }

    // POST /api/lunch-orders/show
    public function show(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $order = LunchOrder::with('user')->find($request->id);

        if (!$order) {
            return apiError('Không tìm thấy đơn đặt cơm', 404);
        }

        return apiSuccess($order, 'Chi tiết đơn đặt cơm');
    }

    // POST /api/lunch-orders/create
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'        => 'required|uuid|exists:users,id',
            'price'          => 'required|integer|min:0',
            'description'    => 'nullable|string|max:255',
            'status'         => 'nullable|string|in:pending,paid,failed',
            'payment_method' => 'nullable|string|in:vnpay,onepay',
        ]);

        // Mặc định status là pending nếu không truyền
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        $order = LunchOrder::create($data);
        $paymentUrl = null;

        // Nếu có chọn cổng thanh toán, tạo URL ngay
        if (!empty($data['payment_method']) && $data['status'] == 'pending') {
            $paymentUrl = $this->generatePaymentUrl($order, $data['payment_method']);
        }

        return apiSuccess([
            'order' => $order,
            'payment_url' => $paymentUrl
        ], 'Tạo đơn đặt cơm thành công', 201);
    }

    // POST /api/lunch-orders/repay
    public function repay(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'payment_method' => 'required|string|in:vnpay,onepay'
        ]);

        $order = LunchOrder::find($request->id);

        if (!$order) {
            return apiError('Không tìm thấy đơn hàng', 404);
        }

        if ($order->status == 'paid') {
            return apiError('Đơn hàng đã được thanh toán', 400);
        }

        // Update payment method mới
        $order->update(['payment_method' => $request->payment_method]);

        $paymentUrl = $this->generatePaymentUrl($order, $request->payment_method);

        return apiSuccess([
            'order' => $order,
            'payment_url' => $paymentUrl
        ], 'Tạo link thanh toán thành công');
    }

    /**
     * Logic tạo URL thanh toán chung
     */
    private function generatePaymentUrl(LunchOrder $order, string $gateway)
    {
        $sessionId = Str::uuid()->toString();
        $paymentUrl = null;

        if ($gateway === 'vnpay') {
            // Log VNPAY logic
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_PAYMENT_INITIATED, [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'vnp_amount' => $order->price * 100,
                'session_id' => $sessionId,
                'description' => "[API] Initiated payment for order #{$order->id}",
                'ip_address' => request()->ip(),
                'raw_data' => ['source' => 'api']
            ]);

            $paymentUrl = VnpayController::getPaymentUrl($order, 'VNBANK', $sessionId);

        } elseif ($gateway === 'onepay') {
            $onepayService = app(OnepayService::class);
            $txnRef = 'HR' . $order->id . '_' . time();
            
            // Log OnePay Logic
            OnepayTransactionLog::logEvent(
                userId: $order->user_id,
                orderId: $order->id,
                event: OnepayTransactionLog::EVENT_PAYMENT_INITIATED,
                status: 'pending',
                txnRef: $txnRef,
                amount: $order->price,
                message: "[API] Initiated payment for order #{$order->id}",
                rawData: ['session_id' => $sessionId]
            );

            // Update txn_ref
            $order->update(['txn_ref' => $txnRef]);

            $paymentUrl = $onepayService->createPaymentUrl(
                orderId: $txnRef,
                amount: $order->price,
                customerIp: request()->ip(),
                orderInfo: "Thanh toan don hang #{$order->id}",
                returnUrl: route('onepay.return') // API vẫn dùng Web return URL để user thấy kết quả
            );
        }

        return $paymentUrl;
    }

    // POST /api/lunch-orders/update
    public function update(Request $request)
    {
        $data = $request->validate([
            'id'              => 'required|integer',
            'price'           => 'sometimes|required|integer|min:0',
            'description'     => 'nullable|string|max:255',
            'status'          => 'sometimes|required|string|in:pending,paid,failed',
            'transaction_code' => 'nullable|string|max:255',
            'payment_method'  => 'nullable|string|in:vnpay,onepay',
            'txn_ref'         => 'nullable|string|max:100',
        ]);

        $order = LunchOrder::find($data['id']);

        if (!$order) {
            return apiError('Không tìm thấy đơn đặt cơm', 404);
        }

        $order->update($data);

        return apiSuccess($order, 'Cập nhật đơn đặt cơm thành công');
    }

    // POST /api/lunch-orders/delete
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $order = LunchOrder::find($request->id);

        if (!$order) {
            return apiError('Không tìm thấy đơn đặt cơm', 404);
        }

        $order->delete();

        return apiSuccess(null, 'Xóa đơn đặt cơm thành công');
    }

    // POST /api/lunch-orders/stats
    public function stats(Request $request)
    {
        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $query = LunchOrder::with('user')
            ->where('status', 'paid')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($day) {
            $query->whereDay('created_at', $day);
        }

        $totalRevenue = (clone $query)->sum('price');
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Thống kê VNPay
        $vnpayStats = [
            'total' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->count(),
            'success' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'success')->count(),
            'failed' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'failed')->count(),
        ];

        // Thống kê OnePay
        $onepayStats = [
            'total' => OnepayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->count(),
            'success' => OnepayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'success')->count(),
            'failed' => OnepayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'failed')->count(),
        ];

        return apiSuccess([
            'total_revenue' => $totalRevenue,
            'orders' => $orders,
            'vnpay_stats' => $vnpayStats,
            'onepay_stats' => $onepayStats,
            'filters' => compact('day', 'month', 'year'),
        ], 'Thống kê đặt cơm trưa');
    }

    // POST /api/lunch-orders/user-logs
    public function userLogs(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id'
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);

        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $status = $request->input('status');

        $query = VnpayTransactionLog::where('user_id', $request->user_id)
            ->with('order')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($day) {
            $query->whereDay('created_at', $day);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => VnpayTransactionLog::where('user_id', $request->user_id)
                ->whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->count(),
            'success' => VnpayTransactionLog::where('user_id', $request->user_id)
                ->whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'success')->count(),
            'failed' => VnpayTransactionLog::where('user_id', $request->user_id)
                ->whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'failed')->count(),
        ];

        return apiSuccess([
            'user' => $user,
            'logs' => $logs,
            'stats' => $stats,
            'filters' => compact('day', 'month', 'year', 'status'),
        ], 'Log thanh toán của user');
    }

    // POST /api/lunch-orders/all-logs
    public function allLogs(Request $request)
    {
        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $status = $request->input('status');
        $search = $request->input('search');
        $gateway = $request->input('gateway'); // vnpay, onepay, hoặc all

        // Query VNPay logs
        $vnpayQuery = VnpayTransactionLog::with(['user', 'order'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        // Query OnePay logs
        $onepayQuery = OnepayTransactionLog::with(['user', 'order'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($day) {
            $vnpayQuery->whereDay('created_at', $day);
            $onepayQuery->whereDay('created_at', $day);
        }

        if ($status) {
            $vnpayQuery->where('status', $status);
            $onepayQuery->where('status', $status);
        }

        if ($search) {
            $vnpayQuery->where(function($q) use ($search) {
                $q->where('vnp_txn_ref', 'like', "%{$search}%")
                  ->orWhere('vnp_transaction_no', 'like', "%{$search}%")
                  ->orWhere('order_id', $search)
                  ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
            $onepayQuery->where(function($q) use ($search) {
                $q->where('txn_ref', 'like', "%{$search}%")
                  ->orWhere('order_id', $search)
                  ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // Lọc theo gateway
        if ($gateway == 'onepay') {
            $vnpayLogs = collect();
            $onepayLogs = $onepayQuery->orderBy('created_at', 'desc')->get();
        } elseif ($gateway == 'vnpay') {
            $vnpayLogs = $vnpayQuery->orderBy('created_at', 'desc')->get();
            $onepayLogs = collect();
        } else {
            $vnpayLogs = $vnpayQuery->orderBy('created_at', 'desc')->get();
            $onepayLogs = $onepayQuery->orderBy('created_at', 'desc')->get();
        }

        // Gắn gateway label
        $vnpayLogs->each(fn($log) => $log->gateway = 'vnpay');
        $onepayLogs->each(fn($log) => $log->gateway = 'onepay');

        $allLogs = $vnpayLogs->concat($onepayLogs)->sortByDesc('created_at')->values();

        // Paginate thủ công
        $page = $request->input('page', 1);
        $perPage = 50;
        $logs = new \Illuminate\Pagination\LengthAwarePaginator(
            $allLogs->forPage($page, $perPage)->values(),
            $allLogs->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return apiSuccess([
            'logs' => $logs,
            'summary' => [
                'vnpay_count' => $vnpayLogs->count(),
                'onepay_count' => $onepayLogs->count(),
                'total' => $allLogs->count(),
            ],
            'filters' => compact('day', 'month', 'year', 'status', 'search', 'gateway'),
        ], 'Toàn bộ log thanh toán');
    }
}
