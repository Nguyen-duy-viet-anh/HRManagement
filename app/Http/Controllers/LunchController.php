<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LunchOrder;
use App\Models\LunchPrice;
use App\Models\VnpayTransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            'payment_method' => $request->payment_method,
            'ip' => $request->ip()
        ]);

        $request->validate([
            'price_level' => 'required|integer|exists:lunch_prices,price',
        ]);

        // Tạo session_id để nhóm các log
        $sessionId = Str::uuid()->toString();

        $order = LunchOrder::create([
            'user_id' => Auth::id(),
            'price' => $request->price_level,
            'description' => "Mua suat an " . number_format($request->price_level),
            'status' => 'pending'
        ]);

        Log::info('[LunchController@order] Đơn hàng đã tạo', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'amount' => $request->price_level,
            'status' => 'pending',
            'session_id' => $sessionId
        ]);

        // Log: Bắt đầu thanh toán
        VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_PAYMENT_INITIATED, [
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'vnp_amount' => $request->price_level * 100,
            'session_id' => $sessionId,
            'description' => "Người dùng " . Auth::user()->name . " bắt đầu thanh toán đơn hàng #{$order->id} với số tiền " . number_format($request->price_level) . "đ",
            'ip_address' => $request->ip(),
            'raw_data' => [
                'price_level' => $request->price_level,
                'payment_method' => $request->payment_method,
                'user_agent' => $request->userAgent(),
            ],
        ]);

        Log::info('[LunchController@order] END - Chuyển hướng đến VNPay', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'session_id' => $sessionId
        ]);

        return $this->createVnpayUrl($order, $request->payment_method, $sessionId);
    }

    public function repay($id)
    {
        Log::info('[LunchController@repay] START - Thanh toán lại', [
            'order_id' => $id,
            'user_id' => Auth::id()
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

        // Tạo session_id mới cho lần thanh toán lại
        $sessionId = Str::uuid()->toString();

        Log::info('[LunchController@repay] Đơn hàng hợp lệ, tiến hành thanh toán lại', [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'amount' => $order->price,
            'session_id' => $sessionId
        ]);

        // Log: Thanh toán lại
        VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_PAYMENT_INITIATED, [
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'vnp_amount' => $order->price * 100,
            'session_id' => $sessionId,
            'description' => "Người dùng " . Auth::user()->name . " thanh toán lại đơn hàng #{$order->id}",
            'ip_address' => request()->ip(),
            'raw_data' => ['action' => 'repay', 'original_status' => $order->status],
        ]);

        Log::info('[LunchController@repay] END - Chuyển hướng đến VNPay', [
            'order_id' => $order->id,
            'session_id' => $sessionId
        ]);

        return $this->createVnpayUrl($order, 'VNBANK', $sessionId); 
    }

    public function vnpayReturn(Request $request)
    {
        Log::info('[LunchController@vnpayReturn] START - VNPay callback', [
            'vnp_TxnRef' => $request->vnp_TxnRef,
            'vnp_ResponseCode' => $request->vnp_ResponseCode,
            'vnp_TransactionNo' => $request->vnp_TransactionNo,
            'vnp_Amount' => $request->vnp_Amount,
            'vnp_BankCode' => $request->vnp_BankCode,
            'ip' => $request->ip()
        ]);

        $vnp_HashSecret = env('VNP_HASH_SECRET'); 
        // $vnp_HashSecret = 'TKW7SK1HSP0VKDRM0YOUQVWCW0DTTFL8';

        $inputData = array();
        foreach ($request->all() as $key => $value) {
            // Chỉ lấy các tham số bắt đầu bằng "vnp_"
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        // 3. Xóa bỏ chữ ký (vnp_SecureHash) khỏi mảng dữ liệu để tính toán lại
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        
        // 4. Sắp xếp
        ksort($inputData);
        
        // 5. Tạo chuỗi hash 
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // 6. Mã hóa
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Lấy session_id từ log trước đó
        $parts = explode('_', $request->vnp_TxnRef);
        $orderId = $parts[0];
        $order = LunchOrder::find($orderId);

        Log::info('[LunchController@vnpayReturn] Xử lý đơn hàng', [
            'order_id' => $orderId,
            'order_exists' => $order ? 'yes' : 'no',
            'user_id' => $order->user_id ?? 'N/A'
        ]);
        
        // Tìm session_id từ log gần nhất của order này
        $lastLog = VnpayTransactionLog::where('order_id', $orderId)
            ->whereNotNull('session_id')
            ->orderBy('created_at', 'desc')
            ->first();
        $sessionId = $lastLog ? $lastLog->session_id : Str::uuid()->toString();
        
        // 7. KIỂM TRA & DEBUG
        if ($secureHash == $vnp_SecureHash) {
            Log::info('[LunchController@vnpayReturn] Checksum hợp lệ', [
                'order_id' => $orderId,
                'vnp_ResponseCode' => $request->vnp_ResponseCode
            ]);

            // Xác định trạng thái giao dịch
            $transactionStatus = $request->vnp_ResponseCode == '00' ? 'success' : 'failed';

            // Log: VNPay trả về kết quả
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_VNPAY_RETURN, [
                'user_id' => $order ? $order->user_id : null,
                'order_id' => $orderId,
                'vnp_txn_ref' => $request->vnp_TxnRef,
                'vnp_transaction_no' => $request->vnp_TransactionNo,
                'vnp_amount' => $request->vnp_Amount,
                'vnp_bank_code' => $request->vnp_BankCode,
                'vnp_bank_tran_no' => $request->vnp_BankTranNo,
                'vnp_card_type' => $request->vnp_CardType,
                'vnp_order_info' => $request->vnp_OrderInfo,
                'vnp_pay_date' => $request->vnp_PayDate,
                'vnp_response_code' => $request->vnp_ResponseCode,
                'vnp_tmn_code' => $request->vnp_TmnCode,
                'vnp_transaction_status' => $request->vnp_TransactionStatus,
                'status' => $transactionStatus,
                'session_id' => $sessionId,
                'description' => "VNPay trả về kết quả: " . ($transactionStatus == 'success' ? 'Thành công' : 'Thất bại') . " - Mã phản hồi: {$request->vnp_ResponseCode}",
                'raw_data' => $request->all(),
                'ip_address' => $request->ip(),
            ]);

            if ($order) {
                if ($request->vnp_ResponseCode == '00') {
                    $order->update([
                        'status' => 'paid', 
                        'transaction_code' => $request->vnp_TransactionNo
                    ]);

                    Log::info('[LunchController@vnpayReturn] SUCCESS - Thanh toán thành công', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'amount' => $request->vnp_Amount / 100,
                        'vnp_TransactionNo' => $request->vnp_TransactionNo,
                        'vnp_BankCode' => $request->vnp_BankCode,
                        'status' => 'paid'
                    ]);

                    // Log: Cập nhật đơn hàng thành công
                    VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'vnp_transaction_no' => $request->vnp_TransactionNo,
                        'vnp_amount' => $request->vnp_Amount,
                        'status' => 'success',
                        'session_id' => $sessionId,
                        'description' => "Đơn hàng #{$order->id} đã được cập nhật trạng thái thành 'paid'. Mã giao dịch VNPay: {$request->vnp_TransactionNo}",
                        'ip_address' => $request->ip(),
                    ]);

                    return redirect()->route('lunch.index')->with('success', 'Thanh toán thành công.');
                } else {
                    $order->update(['status' => 'failed']);

                    Log::warning('[LunchController@vnpayReturn] FAILED - Giao dịch thất bại', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'amount' => $request->vnp_Amount / 100,
                        'vnp_ResponseCode' => $request->vnp_ResponseCode,
                        'vnp_BankCode' => $request->vnp_BankCode,
                        'status' => 'failed'
                    ]);

                    // Log: Cập nhật đơn hàng thất bại
                    VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'vnp_response_code' => $request->vnp_ResponseCode,
                        'vnp_amount' => $request->vnp_Amount,
                        'status' => 'failed',
                        'session_id' => $sessionId,
                        'description' => "Đơn hàng #{$order->id} cập nhật trạng thái 'failed'. Lý do: Mã phản hồi {$request->vnp_ResponseCode}",
                        'ip_address' => $request->ip(),
                    ]);

                    return redirect()->route('lunch.index')->with('error', 'Giao dịch bị lỗi hoặc bị hủy.');
                }
            } else {
                Log::error('[LunchController@vnpayReturn] ERROR - Không tìm thấy đơn hàng', [
                    'order_id' => $orderId,
                    'vnp_TxnRef' => $request->vnp_TxnRef
                ]);
                return redirect()->route('lunch.index')->with('error', 'Không tìm thấy đơn hàng.');
            }

        } else {
            Log::error('[LunchController@vnpayReturn] ERROR - Checksum không khớp', [
                'order_id' => $orderId,
                'vnp_TxnRef' => $request->vnp_TxnRef,
                'ip' => $request->ip()
            ]);

            
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_CHECKSUM_FAILED, [
                'user_id' => $order ? $order->user_id : null,
                'order_id' => $orderId,
                'vnp_txn_ref' => $request->vnp_TxnRef,
                'vnp_transaction_no' => $request->vnp_TransactionNo,
                'vnp_amount' => $request->vnp_Amount,
                'vnp_bank_code' => $request->vnp_BankCode,
                'vnp_response_code' => $request->vnp_ResponseCode,
                'status' => 'failed',
                'session_id' => $sessionId,
                'description' => "LỖI: Checksum không khớp!",
                'raw_data' => array_merge($request->all(), [
                    'error' => 'Checksum failed',
                ]),
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('lunch.index')->with('error', 'Lỗi xác thực giao dịch. Vui lòng liên hệ admin.');
        }
    }

    /**
     * IPN - Instant Payment Notification (Server-to-Server)
     * VNPay gọi trực tiếp đến server, không qua browser
     * Đảm bảo đơn hàng được cập nhật ngay cả khi user đóng trình duyệt
     */
    public function vnpayIPN(Request $request)
    {
        Log::info('[LunchController@vnpayIPN] START - IPN từ VNPay', [
            'vnp_TxnRef' => $request->vnp_TxnRef,
            'vnp_ResponseCode' => $request->vnp_ResponseCode,
            'vnp_TransactionNo' => $request->vnp_TransactionNo,
            'vnp_Amount' => $request->vnp_Amount,
            'ip' => $request->ip()
        ]);

        $returnData = [];
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        // Lấy tất cả params bắt đầu bằng vnp_
        $inputData = [];
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        // Lấy secure hash và xóa khỏi input để tính toán
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);

        // Sắp xếp theo key
        ksort($inputData);

        // Tạo hash string
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // Tính secure hash
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Lấy order
        $parts = explode('_', $request->vnp_TxnRef ?? '');
        $orderId = $parts[0] ?? null;
        $order = $orderId ? LunchOrder::find($orderId) : null;

        // Tìm session_id từ log trước
        $lastLog = VnpayTransactionLog::where('order_id', $orderId)
            ->whereNotNull('session_id')
            ->orderBy('created_at', 'desc')
            ->first();
        $sessionId = $lastLog ? $lastLog->session_id : Str::uuid()->toString();

        try {
            // Kiểm tra checksum
            if ($secureHash !== $vnp_SecureHash) {
                Log::error('[LunchController@vnpayIPN] Checksum không hợp lệ');
                
                VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_CHECKSUM_FAILED, [
                    'user_id' => $order?->user_id,
                    'order_id' => $orderId,
                    'vnp_txn_ref' => $request->vnp_TxnRef,
                    'vnp_response_code' => $request->vnp_ResponseCode,
                    'status' => 'failed',
                    'session_id' => $sessionId,
                    'description' => '[IPN] Checksum không hợp lệ',
                    'raw_data' => $request->all(),
                    'ip_address' => $request->ip(),
                ]);

                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Invalid Checksum';
                return response()->json($returnData);
            }

            // Kiểm tra order tồn tại
            if (!$order) {
                Log::error('[LunchController@vnpayIPN] Không tìm thấy đơn hàng', ['order_id' => $orderId]);
                $returnData['RspCode'] = '01';
                $returnData['Message'] = 'Order not found';
                return response()->json($returnData);
            }

            // Kiểm tra số tiền
            $vnpAmount = intval($request->vnp_Amount) / 100;
            if ($vnpAmount != $order->price) {
                Log::error('[LunchController@vnpayIPN] Số tiền không khớp', [
                    'vnp_amount' => $vnpAmount,
                    'order_price' => $order->price
                ]);
                $returnData['RspCode'] = '04';
                $returnData['Message'] = 'Invalid Amount';
                return response()->json($returnData);
            }

            // Kiểm tra đơn hàng đã xử lý chưa
            if ($order->status === 'paid') {
                Log::info('[LunchController@vnpayIPN] Đơn hàng đã được xử lý trước đó', ['order_id' => $orderId]);
                $returnData['RspCode'] = '02';
                $returnData['Message'] = 'Order already confirmed';
                return response()->json($returnData);
            }

            // Xử lý theo response code
            $transactionStatus = $request->vnp_ResponseCode == '00' ? 'success' : 'failed';

            // Log IPN received
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_IPN_RECEIVED, [
                'user_id' => $order->user_id,
                'order_id' => $orderId,
                'vnp_txn_ref' => $request->vnp_TxnRef,
                'vnp_transaction_no' => $request->vnp_TransactionNo,
                'vnp_amount' => $request->vnp_Amount,
                'vnp_bank_code' => $request->vnp_BankCode,
                'vnp_bank_tran_no' => $request->vnp_BankTranNo,
                'vnp_card_type' => $request->vnp_CardType,
                'vnp_order_info' => $request->vnp_OrderInfo,
                'vnp_pay_date' => $request->vnp_PayDate,
                'vnp_response_code' => $request->vnp_ResponseCode,
                'vnp_tmn_code' => $request->vnp_TmnCode,
                'vnp_transaction_status' => $request->vnp_TransactionStatus,
                'status' => $transactionStatus,
                'session_id' => $sessionId,
                'description' => "[IPN] VNPay callback - " . ($transactionStatus == 'success' ? 'Thành công' : 'Thất bại'),
                'raw_data' => $request->all(),
                'ip_address' => $request->ip(),
            ]);

            if ($request->vnp_ResponseCode == '00') {
                // Thanh toán thành công
                $order->update([
                    'status' => 'paid',
                    'transaction_code' => $request->vnp_TransactionNo
                ]);

                Log::info('[LunchController@vnpayIPN] SUCCESS - Cập nhật đơn hàng thành công', [
                    'order_id' => $order->id,
                    'status' => 'paid'
                ]);

                // Log order updated
                VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'vnp_transaction_no' => $request->vnp_TransactionNo,
                    'status' => 'success',
                    'session_id' => $sessionId,
                    'description' => "[IPN] Đơn hàng #{$order->id} đã cập nhật thành 'paid'",
                    'ip_address' => $request->ip(),
                ]);

                $returnData['RspCode'] = '00';
                $returnData['Message'] = 'Confirm Success';
            } else {
                // Thanh toán thất bại
                $order->update(['status' => 'failed']);

                Log::warning('[LunchController@vnpayIPN] FAILED - Giao dịch thất bại', [
                    'order_id' => $order->id,
                    'vnp_ResponseCode' => $request->vnp_ResponseCode
                ]);

                $returnData['RspCode'] = '00';
                $returnData['Message'] = 'Confirm Success';
            }

        } catch (\Exception $e) {
            Log::error('[LunchController@vnpayIPN] Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknown error';
        }

        return response()->json($returnData);
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

    // 7. Xem toàn bộ log VNPay
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

        $query = VnpayTransactionLog::with(['user', 'order'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($day) {
            $query->whereDay('created_at', $day);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('vnp_txn_ref', 'like', "%{$search}%")
                  ->orWhere('vnp_transaction_no', 'like', "%{$search}%")
                  ->orWhere('order_id', $search)
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        // Thống kê
        $stats = [
            'total' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->count(),
            'success' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'success')->count(),
            'failed' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->where('status', 'failed')->count(),
            'pending' => VnpayTransactionLog::whereYear('created_at', $year)->whereMonth('created_at', $month)->when($day, fn($q) => $q->whereDay('created_at', $day))->whereNull('status')->count(),
        ];

        // Lấy danh sách đơn hàng pending để có thể update
        $pendingOrders = LunchOrder::where('status', 'pending')->pluck('id')->toArray();

        Log::info('[LunchController@allLogs] END - Success', [
            'logs_count' => $logs->total(),
            'stats' => $stats
        ]);

        return view('lunch.vnpay-logs', compact('logs', 'stats', 'day', 'month', 'year', 'status', 'search', 'pendingOrders'));
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
        VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'status' => $request->status,
            'description' => "Admin " . Auth::user()->name . " đã cập nhật đơn hàng #{$order->id} từ '{$oldStatus}' thành '{$request->status}'",
            'ip_address' => $request->ip(),
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
//
    private function createVnpayUrl($order, $paymentMethod = 'VNBANK', $sessionId = null)
    {
        Log::info('[LunchController@createVnpayUrl] START - Tạo URL VNPay', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $order->price,
            'payment_method' => $paymentMethod,
            'session_id' => $sessionId
        ]);

        $vnp_Url = env('VNP_URL');
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_TmnCode = env('VNP_TMN_CODE');

        // 1. Luôn tạo mã mới để tránh trùng lặp khi bấm lại
        $vnp_TxnRef = $order->id . "_" . date('YmdHis'); 

        $vnp_OrderInfo = "Thanh_toan_don_" . $order->id;

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => (int)$order->price * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => "127.0.0.1",
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => env('VNP_RETURN_URL'),
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        if ($paymentMethod == 'VNPAYQR') {
            $inputData['vnp_BankCode'] = 'VNPAYQR';
        } elseif ($paymentMethod == 'VNBANK') {
            $inputData['vnp_BankCode'] = 'VNBANK';
        }

        // 3. Sắp xếp
        ksort($inputData);
        $query = http_build_query($inputData);
        $vnp_Url = $vnp_Url . "?" . $query;
        
        // 4. Tạo Hash
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        Log::info('[LunchController@createVnpayUrl] URL đã tạo', [
            'order_id' => $order->id,
            'vnp_TxnRef' => $vnp_TxnRef,
            'vnp_Amount' => (int)$order->price * 100,
            'vnp_BankCode' => $inputData['vnp_BankCode'] ?? 'N/A'
        ]);

        // Log: Chuyển hướng đến VNPay
        if ($sessionId) {
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_REDIRECT_TO_VNPAY, [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'vnp_txn_ref' => $vnp_TxnRef,
                'vnp_amount' => (int)$order->price * 100,
                'session_id' => $sessionId,
                'description' => "Chuyển hướng người dùng đến cổng thanh toán VNPay. TxnRef: {$vnp_TxnRef}",
                'ip_address' => request()->ip(),
                'raw_data' => [
                    'payment_method' => $paymentMethod,
                    'input_data' => $inputData,
                ],
            ]);
        }

        Log::info('[LunchController@createVnpayUrl] END - Redirect to VNPay', [
            'order_id' => $order->id,
            'vnp_TxnRef' => $vnp_TxnRef
        ]);

        // 5. Redirect kèm Header chống Cache
        return redirect($vnp_Url)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
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