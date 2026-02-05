<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LunchOrder;
use App\Models\LunchPrice;
use App\Models\VnpayTransactionLog;
use App\Models\OnepayTransactionLog;
use App\Models\User;
use App\Services\OnepayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LunchController extends Controller
{
    // 1. Danh sách & Giao diện mua
    public function index()
    {
        Log::info('[LunchController@index] START - Load danh sách đơn hàng', [
            'user_id' => Auth::id()
        ]);

        $myOrders = LunchOrder::where('user_id', Auth::id())
                              ->orderBy('created_at', 'desc')
                              ->paginate(20);
        
        $prices = LunchPrice::orderBy('price', 'asc')->get();

        Log::info('[LunchController@index] END - Success', [
            'user_id' => Auth::id(),
            'orders_count' => $myOrders->total(),
            'prices_count' => $prices->count()
        ]);

        return view('lunch.index', compact('myOrders', 'prices'));
    }

    // 2. Tạo đơn hàng MỚI
    public function order(Request $request)
    {
        Log::info('[LunchController@order] START - Tạo đơn hàng mới', [
            'user_id' => Auth::id(),
            'price_level' => $request->price_level,
            'payment_gateway' => $request->payment_gateway,
            'ip' => $request->ip()
        ]);

        $request->validate([
            'price_level' => 'required|integer|exists:lunch_prices,price',
            'payment_gateway' => 'nullable|in:vnpay,onepay',
        ]);

        // Xác định cổng thanh toán
        $gateway = $request->payment_gateway ?? 'vnpay';

        // Tạo session_id để nhóm các log
        $sessionId = Str::uuid()->toString();

        $order = LunchOrder::create([
            'user_id' => Auth::id(),
            'price' => $request->price_level,
            'description' => "Mua suat an " . number_format($request->price_level),
            'status' => 'pending',
            'payment_method' => $gateway, // Lưu cổng thanh toán
        ]);

        Log::info('[LunchController@order] Đơn hàng đã tạo', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'amount' => $request->price_level,
            'status' => 'pending',
            'gateway' => $gateway,
            'session_id' => $sessionId
        ]);

        // Chuyển hướng theo cổng thanh toán được chọn
        if ($gateway === 'onepay') {
            return $this->createOnepayUrl($order, $sessionId);
        }

        // Mặc định: VNPay
        // Log: Bắt đầu thanh toán
        VnpayTransactionLog::create([
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'event_type' => 'payment_created',
            'vnp_amount' => $request->price_level * 100,
            'status' => 'pending',
            'description' => "Người dùng " . Auth::user()->name . " bắt đầu thanh toán đơn hàng #{$order->id} với số tiền " . number_format($request->price_level) . "đ",
            'raw_data' => [
                'price_level' => $request->price_level,
                'payment_method' => $request->payment_method,
            ],
        ]);

        Log::info('[LunchController@order] END - Chuyển hướng đến VNPay', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'session_id' => $sessionId
        ]);

        return VnpayController::createPaymentUrl($order, $request->payment_method ?? 'VNBANK', $sessionId);
    }

    // 2.1 Tạo URL thanh toán OnePay
    protected function createOnepayUrl(LunchOrder $order, string $sessionId)
    {
        // Kiểm tra lại status trước khi tạo URL thanh toán (tránh race condition)
        $order->refresh();
        if ($order->status === 'paid') {
            Log::warning('[LunchController@createOnepayUrl] Đơn hàng đã thanh toán', [
                'order_id' => $order->id,
                'user_id' => Auth::id()
            ]);
            return redirect()->route('lunch.index')
                ->with('error', 'Đơn hàng đã được thanh toán trước đó.');
        }

        $onepayService = app(OnepayService::class);
        
        // Tạo mã giao dịch unique
        $txnRef = 'HR' . $order->id . '_' . time();
        
        // Cập nhật txn_ref vào order
        $order->update(['txn_ref' => $txnRef]);
        
        // Log: Bắt đầu thanh toán OnePay
        OnepayTransactionLog::logEvent(
            userId: Auth::id(),
            orderId: $order->id,
            event: OnepayTransactionLog::EVENT_PAYMENT_INITIATED,
            status: 'pending',
            txnRef: $txnRef,
            amount: $order->price,
            message: "Khởi tạo thanh toán OnePay cho đơn hàng #{$order->id}",
            rawData: [
                'session_id' => $sessionId,
                'customer_ip' => request()->ip(),
            ]
        );
        
        // Tạo URL thanh toán
        $paymentUrl = $onepayService->createPaymentUrl(
            orderId: $txnRef,
            amount: $order->price,
            customerIp: request()->ip(),
            orderInfo: "Thanh toan don hang #{$order->id}",
            returnUrl: route('onepay.return')
        );
        
        // Log: Chuyển hướng
        OnepayTransactionLog::logEvent(
            userId: Auth::id(),
            orderId: $order->id,
            event: OnepayTransactionLog::EVENT_REDIRECT_TO_ONEPAY,
            status: 'pending',
            txnRef: $txnRef,
            amount: $order->price,
            message: 'Chuyển hướng đến cổng thanh toán OnePay',
            rawData: ['payment_url' => $paymentUrl]
        );
        
        Log::info('[LunchController@createOnepayUrl] Chuyển hướng đến OnePay', [
            'order_id' => $order->id,
            'txn_ref' => $txnRef,
            'session_id' => $sessionId
        ]);
        
        return redirect()->away($paymentUrl);
    }

    public function repay($id, Request $request)
    {
        // Xác định cổng thanh toán
        $gateway = $request->query('gateway', 'vnpay');
        
        Log::info('[LunchController@repay] START - Thanh toán lại', [
            'order_id' => $id,
            'user_id' => Auth::id(),
            'gateway' => $gateway
        ]);

        $order = LunchOrder::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$order || $order->status != 'pending') {
            Log::warning('[LunchController@repay] FAILED - Đơn hàng không hợp lệ', [
                'order_id' => $id,
                'user_id' => Auth::id(),
                'order_exists' => $order ? 'yes' : 'no',
                'order_status' => $order->status ?? 'N/A'
            ]);
            return redirect()->route('lunch.index')->with('error', 'Đơn hàng không hợp lệ.');
        }

        // Cập nhật cổng thanh toán
        $order->update(['payment_method' => $gateway]);

        // Tạo session_id mới cho lần thanh toán lại
        $sessionId = Str::uuid()->toString();

        Log::info('[LunchController@repay] Đơn hàng hợp lệ, tiến hành thanh toán lại', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'amount' => $order->price,
            'gateway' => $gateway,
            'session_id' => $sessionId
        ]);

        // Chuyển hướng theo cổng thanh toán được chọn
        if ($gateway === 'onepay') {
            return $this->createOnepayUrl($order, $sessionId);
        }

        // Log: Thanh toán lại
        VnpayTransactionLog::create([
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'event_type' => 'payment_created',
            'vnp_amount' => $order->price * 100,
            'status' => 'pending',
            'description' => "Người dùng " . Auth::user()->name . " thanh toán lại đơn hàng #{$order->id}",
            'raw_data' => ['action' => 'repay', 'original_status' => $order->status],
        ]);

        Log::info('[LunchController@repay] END - Chuyển hướng đến VNPay', [
            'order_id' => $order->id,
            'session_id' => $sessionId
        ]);

        return VnpayController::createPaymentUrl($order, 'VNBANK', $sessionId); 
    }

    // 5. Thống kê
    public function stats(Request $request)
    {
        Log::info('[LunchController@stats] START - Load thống kê', [
            'day' => $request->input('day'),
            'month' => $request->input('month', date('m')),
            'year' => $request->input('year', date('Y')),
            'user_id' => Auth::id()
        ]);

        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $query = \App\Models\LunchOrder::with('user')
            ->where('status', 'paid')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($day) {
            $query->whereDay('created_at', $day);
        }

        $totalRevenue = $query->sum('price');
        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        // Thống kê giao dịch VNPay
        $vnpayStats = [
            'total' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->count(),
            'success' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'success')->count(),
            'failed' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'failed')->count(),
        ];

        Log::info('[LunchController@stats] END - Success', [
            'total_revenue' => $totalRevenue,
            'orders_count' => $orders->total(),
            'vnpay_total' => $vnpayStats['total'],
            'vnpay_success' => $vnpayStats['success'],
            'vnpay_failed' => $vnpayStats['failed']
        ]);

        return view('lunch.stats', compact('orders', 'totalRevenue', 'day', 'month', 'year', 'vnpayStats'));
    }

    // 6. Xem log chi tiết của 1 user
    public function userLogs(Request $request, $userId)
    {
        Log::info('[LunchController@userLogs] START - Xem log user', [
            'target_user_id' => $userId,
            'admin_user_id' => Auth::id()
        ]);

        $user = User::findOrFail($userId);
        
        // Lọc theo ngày/tháng/năm
        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $status = $request->input('status');

        $query = VnpayTransactionLog::where('user_id', $userId)
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

        // Thống kê cho user này
        $stats = [
            'total' => VnpayTransactionLog::where('user_id', $userId)->whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->count(),
            'success' => VnpayTransactionLog::where('user_id', $userId)->whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'success')->count(),
            'failed' => VnpayTransactionLog::where('user_id', $userId)->whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'failed')->count(),
            'pending' => VnpayTransactionLog::where('user_id', $userId)->whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->whereNull('status')->count(),
        ];

        // Lấy danh sách đơn hàng pending để có thể update
        $pendingOrders = LunchOrder::where('status', 'pending')->pluck('id')->toArray();

        Log::info('[LunchController@userLogs] END - Success', [
            'target_user_id' => $userId,
            'logs_count' => $logs->total()
        ]);

        return view('lunch.vnpay-logs', compact('user', 'logs', 'stats', 'day', 'month', 'year', 'status', 'pendingOrders'));
    }

    // 7. Xem toàn bộ log thanh toán (VNPay + OnePay)
    public function allLogs(Request $request)
    {
        Log::info('[LunchController@allLogs] START - Load tất cả logs', [
            'admin_user_id' => Auth::id()
        ]);

        // Chỉ admin mới được xem
        if (Auth::user()->role != 0) {
            Log::warning('[LunchController@allLogs] DENIED - Không có quyền', [
                'user_id' => Auth::id()
            ]);
            abort(403, 'Chỉ Admin mới được xem trang này.');
        }

        // Lọc theo ngày/tháng/năm
        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $status = $request->input('status');
        $search = $request->input('search');
        $gateway = $request->input('gateway'); // vnpay, onepay hoặc all

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
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
            $onepayQuery->where(function($q) use ($search) {
                $q->where('txn_ref', 'like', "%{$search}%")
                  ->orWhere('order_id', $search)
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Lấy logs theo gateway filter
        if ($gateway == 'onepay') {
            // Chỉ OnePay
            $vnpayLogs = collect();
            $onepayLogs = $onepayQuery->orderBy('created_at', 'desc')->get();
        } elseif ($gateway == 'vnpay') {
            // Chỉ VNPay
            $vnpayLogs = $vnpayQuery->orderBy('created_at', 'desc')->get();
            $onepayLogs = collect();
        } else {
            // Cả 2
            $vnpayLogs = $vnpayQuery->orderBy('created_at', 'desc')->get();
            $onepayLogs = $onepayQuery->orderBy('created_at', 'desc')->get();
        }

        // Gộp logs và thêm thuộc tính gateway
        $vnpayLogs->each(fn($log) => $log->gateway = 'vnpay');
        $onepayLogs->each(fn($log) => $log->gateway = 'onepay');

        $allLogs = $vnpayLogs->concat($onepayLogs)->sortByDesc('created_at')->values();

        // Paginate thủ công
        $page = $request->input('page', 1);
        $perPage = 50;
        $logs = new \Illuminate\Pagination\LengthAwarePaginator(
            $allLogs->forPage($page, $perPage),
            $allLogs->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        Log::info('[LunchController@allLogs] END - Success', [
            'vnpay_count' => $vnpayLogs->count(),
            'onepay_count' => $onepayLogs->count(),
            'total' => $allLogs->count()
        ]);

        return view('lunch.vnpay-logs', compact('logs', 'day', 'month', 'year', 'status', 'search', 'gateway'));
    }

    // 8. Admin cập nhật trạng thái đơn hàng
    public function updateOrderStatus(Request $request, $orderId)
    {
        Log::info('[LunchController@updateOrderStatus] START - Cập nhật đơn hàng', [
            'order_id' => $orderId,
            'admin_user_id' => Auth::id(),
            'new_status' => $request->status,
            'transaction_code' => $request->transaction_code
        ]);

        // Chỉ admin
        if (Auth::user()->role != 0) {
            Log::warning('[LunchController@updateOrderStatus] DENIED - Không có quyền', [
                'user_id' => Auth::id()
            ]);
            abort(403);
        }

        $order = LunchOrder::findOrFail($orderId);
        $oldStatus = $order->status;

        $request->validate([
            'status' => 'required|in:pending,paid,failed',
            'transaction_code' => 'nullable|string|max:255'
        ]);

        $order->update([
            'status' => $request->status,
            'transaction_code' => $request->transaction_code ?? $order->transaction_code
        ]);

        // Log vào database
        VnpayTransactionLog::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'event_type' => 'order_updated',
            'status' => $request->status,
            'description' => "Admin " . Auth::user()->name . " đã cập nhật đơn hàng #{$order->id} từ '{$oldStatus}' thành '{$request->status}'",
            'raw_data' => [
                'admin_id' => Auth::id(),
                'admin_name' => Auth::user()->name,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'transaction_code' => $request->transaction_code
            ]
        ]);

        Log::info('[LunchController@updateOrderStatus] END - Success', [
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $request->status
        ]);

        return back()->with('success', "Đã cập nhật đơn hàng #{$orderId} thành '{$request->status}'");
    }

    // --- QUẢN LÝ CẤU HÌNH GIÁ ---
    public function config()
    {
        Log::info('[LunchController@config] START - Load cấu hình giá', [
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role
        ]);

        if (Auth::user()->role != 0) {
            Log::warning('[LunchController@config] DENIED - Không có quyền truy cập', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role
            ]);
            abort(403, 'Chỉ Admin mới được cấu hình giá.');
        }
        
        $prices = LunchPrice::orderBy('price', 'asc')->get();

        Log::info('[LunchController@config] END - Success', [
            'prices_count' => $prices->count()
        ]);

        return view('lunch.config', compact('prices'));
    }

    public function storePrice(Request $request)
    {
        Log::info('[LunchController@storePrice] START - Thêm mức giá', [
            'user_id' => Auth::id(),
            'price' => $request->price
        ]);

        if (Auth::user()->role != 0) {
            Log::warning('[LunchController@storePrice] DENIED - Không có quyền', [
                'user_id' => Auth::id()
            ]);
            abort(403);
        }
        
        $request->validate([
            'price' => 'required|integer|min:1000|unique:lunch_prices,price'
        ]);

        $priceRecord = LunchPrice::create(['price' => $request->price]);

        Log::info('[LunchController@storePrice] END - Success', [
            'price_id' => $priceRecord->id,
            'price' => $request->price
        ]);

        return back()->with('success', 'Đã thêm mức giá mới.');
    }

    public function deletePrice($id)
    {
        Log::info('[LunchController@deletePrice] START - Xóa mức giá', [
            'user_id' => Auth::id(),
            'price_id' => $id
        ]);

        if (Auth::user()->role != 0) {
            Log::warning('[LunchController@deletePrice] DENIED - Không có quyền', [
                'user_id' => Auth::id()
            ]);
            abort(403);
        }
        
        LunchPrice::destroy($id);

        Log::info('[LunchController@deletePrice] END - Success', [
            'price_id' => $id
        ]);

        return back()->with('success', 'Đã xóa mức giá.');
    }
}