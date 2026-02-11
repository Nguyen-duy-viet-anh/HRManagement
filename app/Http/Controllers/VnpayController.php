<?php

namespace App\Http\Controllers;

use App\Models\LunchOrder;
use App\Models\VnpayTransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VnpayController extends Controller
{
    // ========================================
    // TẠO URL THANH TOÁN
    // ========================================

    /**
     * Tạo URL thanh toán VNPay
     * 
     * @param LunchOrder $order
     * @param string $paymentMethod VNBANK | VNPAYQR
     * @param string|null $sessionId
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Lấy URL thanh toán VNPay (trả về string)
     */
    public static function getPaymentUrl(LunchOrder $order, string $paymentMethod = 'VNBANK', ?string $sessionId = null): string
    {
        $vnp_Url = env('VNP_URL');
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_TmnCode = env('VNP_TMN_CODE');

        // Luôn tạo mã mới để tránh trùng lặp khi bấm lại
        $vnp_TxnRef = $order->id . "_" . date('YmdHis'); 
        $vnp_OrderInfo = "Thanh_toan_don_" . $order->id;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => (int)$order->price * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip() ?? "127.0.0.1",
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => route('vnpay.return'),
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        if ($paymentMethod == 'VNPAYQR') {
            $inputData['vnp_BankCode'] = 'VNPAYQR';
        } elseif ($paymentMethod == 'VNBANK') {
            $inputData['vnp_BankCode'] = 'VNBANK';
        }

        // Sắp xếp theo key
        ksort($inputData);
        $query = http_build_query($inputData);
        $vnp_Url = $vnp_Url . "?" . $query;
        
        // Tạo Hash
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        // Log: Chuyển hướng đến VNPay
        if ($sessionId) {
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_REDIRECT_TO_VNPAY, [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'vnp_txn_ref' => $vnp_TxnRef,
                'vnp_amount' => (int)$order->price * 100,
                'session_id' => $sessionId,
                'description' => "Chuyển hướng đến cổng thanh toán VNPay. TxnRef: {$vnp_TxnRef}",
                'ip_address' => request()->ip(),
                'raw_data' => [
                    'payment_method' => $paymentMethod,
                    'input_data' => $inputData,
                ],
            ]);
        }

        Log::info('[VnpayController@getPaymentUrl] Generated URL', [
            'order_id' => $order->id,
            'vnp_TxnRef' => $vnp_TxnRef,
            'url' => $vnp_Url
        ]);

        return $vnp_Url;
    }

    /**
     * Tạo URL thanh toán VNPay và Redirect (Dùng cho Web)
     */
    public static function createPaymentUrl(LunchOrder $order, string $paymentMethod = 'VNBANK', ?string $sessionId = null)
    {
        // Kiểm tra lại status trước khi tạo URL thanh toán (tránh race condition)
        $order->refresh();
        if ($order->status === 'paid') {
            Log::warning('[VnpayController@createPaymentUrl] Đơn hàng đã thanh toán', [
                'order_id' => $order->id,
                'user_id' => $order->user_id
            ]);
            return redirect()->route('lunch.index')
                ->with('error', 'Đơn hàng đã được thanh toán trước đó.');
        }

        Log::info('[VnpayController@createPaymentUrl] START', [
            'order_id' => $order->id,
            'payment_method' => $paymentMethod
        ]);

        $vnp_Url = self::getPaymentUrl($order, $paymentMethod, $sessionId);

        // Redirect kèm Header chống Cache
        return redirect($vnp_Url)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
    }

    // ========================================
    // XỬ LÝ RETURN URL
    // ========================================

    /**
     * Xử lý Return URL - VNPay redirect user về sau thanh toán
     */
    public function handleReturn(Request $request)
    {
        Log::info('[VnpayController@handleReturn] START', [
            'vnp_TxnRef' => $request->vnp_TxnRef,
            'vnp_ResponseCode' => $request->vnp_ResponseCode,
            'vnp_TransactionNo' => $request->vnp_TransactionNo,
            'vnp_Amount' => $request->vnp_Amount,
            'vnp_BankCode' => $request->vnp_BankCode,
            'ip' => $request->ip()
        ]);

        $vnp_HashSecret = env('VNP_HASH_SECRET');

        // Lấy các params vnp_
        $inputData = [];
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        // Xóa secure hash để tính toán lại
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        
        // Sắp xếp và tạo hash string
        ksort($inputData);
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

        // Lấy order_id từ TxnRef
        $parts = explode('_', $request->vnp_TxnRef);
        $orderId = $parts[0];
        $order = LunchOrder::find($orderId);

        // Tìm session_id từ log trước
        $lastLog = VnpayTransactionLog::where('order_id', $orderId)
            ->whereNotNull('session_id')
            ->orderBy('created_at', 'desc')
            ->first();
        $sessionId = $lastLog ? $lastLog->session_id : Str::uuid()->toString();

        // Kiểm tra checksum
        if ($secureHash == $vnp_SecureHash) {
            Log::info('[VnpayController@handleReturn] Checksum hợp lệ', [
                'order_id' => $orderId,
                'vnp_ResponseCode' => $request->vnp_ResponseCode
            ]);

            $transactionStatus = $request->vnp_ResponseCode == '00' ? 'success' : 'failed';

            // Log: VNPay trả về kết quả
            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_VNPAY_RETURN, [
                'user_id' => $order?->user_id,
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
                'description' => "VNPay trả về: " . ($transactionStatus == 'success' ? 'Thành công' : 'Thất bại') . " - Mã: {$request->vnp_ResponseCode}",
                'raw_data' => $request->all(),
                'ip_address' => $request->ip(),
            ]);

            if ($order) {
                if ($request->vnp_ResponseCode == '00') {
                    // Thanh toán thành công
                    $order->update([
                        'status' => 'paid',
                        'transaction_code' => $request->vnp_TransactionNo
                    ]);

                    Log::info('[VnpayController@handleReturn] SUCCESS', [
                        'order_id' => $order->id,
                        'amount' => $request->vnp_Amount / 100,
                        'vnp_TransactionNo' => $request->vnp_TransactionNo
                    ]);

                    // Log: Cập nhật đơn hàng
                    VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'vnp_transaction_no' => $request->vnp_TransactionNo,
                        'vnp_amount' => $request->vnp_Amount,
                        'status' => 'success',
                        'session_id' => $sessionId,
                        'description' => "Đơn hàng #{$order->id} đã thanh toán thành công. Mã GD: {$request->vnp_TransactionNo}",
                        'ip_address' => $request->ip(),
                    ]);

                    return redirect()->route('lunch.index')->with('success', 'Thanh toán thành công.');
                } else {
                    // Các mã lỗi do user hủy/back - giữ pending để thanh toán lại
                    // 24: User hủy giao dịch
                    // 15: Đã hết thời gian chờ thanh toán
                    // 99: User nhấn nút quay lại (back)
                    $userCancelledCodes = ['24', '15', '99'];
                    
                    if (in_array($request->vnp_ResponseCode, $userCancelledCodes)) {
                        // Giữ pending - cho phép thanh toán lại
                        Log::info('[VnpayController@handleReturn] User cancelled/back - Keep pending', [
                            'order_id' => $order->id,
                            'vnp_ResponseCode' => $request->vnp_ResponseCode
                        ]);

                        VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                            'user_id' => $order->user_id,
                            'order_id' => $order->id,
                            'vnp_response_code' => $request->vnp_ResponseCode,
                            'vnp_amount' => $request->vnp_Amount,
                            'status' => 'pending',
                            'session_id' => $sessionId,
                            'description' => "Đơn hàng #{$order->id} - User hủy/quay lại. Mã: {$request->vnp_ResponseCode} (" . self::getResponseMessage($request->vnp_ResponseCode) . ")",
                            'ip_address' => $request->ip(),
                        ]);

                        return redirect()->route('lunch.index')->with('warning', 'Giao dịch đã bị hủy. Bạn có thể thanh toán lại.');
                    }

                    // Lỗi thực sự - đánh dấu failed
                    $order->update(['status' => 'failed']);

                    Log::warning('[VnpayController@handleReturn] FAILED', [
                        'order_id' => $order->id,
                        'vnp_ResponseCode' => $request->vnp_ResponseCode
                    ]);

                    VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'vnp_response_code' => $request->vnp_ResponseCode,
                        'vnp_amount' => $request->vnp_Amount,
                        'status' => 'failed',
                        'session_id' => $sessionId,
                        'description' => "Đơn hàng #{$order->id} thất bại. Mã phản hồi: {$request->vnp_ResponseCode} (" . self::getResponseMessage($request->vnp_ResponseCode) . ")",
                        'ip_address' => $request->ip(),
                    ]);

                    return redirect()->route('lunch.index')->with('error', 'Giao dịch bị lỗi hoặc bị hủy.');
                }
            } else {
                Log::error('[VnpayController@handleReturn] Order not found', ['order_id' => $orderId]);
                return redirect()->route('lunch.index')->with('error', 'Không tìm thấy đơn hàng.');
            }

        } else {
            // Checksum không khớp
            Log::error('[VnpayController@handleReturn] Checksum không khớp', [
                'order_id' => $orderId,
                'vnp_TxnRef' => $request->vnp_TxnRef
            ]);

            VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_CHECKSUM_FAILED, [
                'user_id' => $order?->user_id,
                'order_id' => $orderId,
                'vnp_txn_ref' => $request->vnp_TxnRef,
                'vnp_transaction_no' => $request->vnp_TransactionNo,
                'vnp_amount' => $request->vnp_Amount,
                'vnp_bank_code' => $request->vnp_BankCode,
                'vnp_response_code' => $request->vnp_ResponseCode,
                'status' => 'failed',
                'session_id' => $sessionId,
                'description' => "LỖI: Checksum không khớp!",
                'raw_data' => $request->all(),
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('lunch.index')->with('error', 'Lỗi xác thực giao dịch. Vui lòng liên hệ admin.');
        }
    }

    // ========================================
    // XỬ LÝ IPN (Server-to-Server)
    // ========================================

    /**
     * Xử lý IPN - VNPay gọi trực tiếp đến server
     * Đảm bảo đơn hàng được cập nhật ngay cả khi user đóng trình duyệt
     */
    public function handleIPN(Request $request)
    {
        Log::info('[VnpayController@handleIPN] START', [
            'vnp_TxnRef' => $request->vnp_TxnRef,
            'vnp_ResponseCode' => $request->vnp_ResponseCode,
            'vnp_TransactionNo' => $request->vnp_TransactionNo,
            'vnp_Amount' => $request->vnp_Amount,
            'ip' => $request->ip()
        ]);

        $returnData = [];
        $vnp_HashSecret = env('VNP_HASH_SECRET');

        // Lấy các params vnp_
        $inputData = [];
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);

        // Sắp xếp và tạo hash
        ksort($inputData);
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
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Lấy order
        $parts = explode('_', $request->vnp_TxnRef ?? '');
        $orderId = $parts[0] ?? null;
        $order = $orderId ? LunchOrder::find($orderId) : null;

        // Tìm session_id
        $lastLog = VnpayTransactionLog::where('order_id', $orderId)
            ->whereNotNull('session_id')
            ->orderBy('created_at', 'desc')
            ->first();
        $sessionId = $lastLog ? $lastLog->session_id : Str::uuid()->toString();

        try {
            // Kiểm tra checksum
            if ($secureHash !== $vnp_SecureHash) {
                Log::error('[VnpayController@handleIPN] Checksum không hợp lệ');
                
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

                return response()->json(['RspCode' => '97', 'Message' => 'Invalid Checksum']);
            }

            // Kiểm tra order
            if (!$order) {
                Log::error('[VnpayController@handleIPN] Order not found', ['order_id' => $orderId]);
                return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
            }

            // Kiểm tra số tiền
            $vnpAmount = intval($request->vnp_Amount) / 100;
            if ($vnpAmount != $order->price) {
                Log::error('[VnpayController@handleIPN] Amount mismatch', [
                    'vnp_amount' => $vnpAmount,
                    'order_price' => $order->price
                ]);
                return response()->json(['RspCode' => '04', 'Message' => 'Invalid Amount']);
            }

            // Kiểm tra đã xử lý chưa
            if ($order->status === 'paid') {
                Log::info('[VnpayController@handleIPN] Order already paid', ['order_id' => $orderId]);
                return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
            }

            $transactionStatus = $request->vnp_ResponseCode == '00' ? 'success' : 'failed';

            // Log IPN
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

                Log::info('[VnpayController@handleIPN] SUCCESS', ['order_id' => $order->id]);

                VnpayTransactionLog::logEvent(VnpayTransactionLog::EVENT_ORDER_UPDATED, [
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'vnp_transaction_no' => $request->vnp_TransactionNo,
                    'status' => 'success',
                    'session_id' => $sessionId,
                    'description' => "[IPN] Đơn hàng #{$order->id} đã thanh toán thành công",
                    'ip_address' => $request->ip(),
                ]);

                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            } else {
                // Các mã lỗi do user hủy/back - giữ pending
                $userCancelledCodes = ['24', '15', '99'];
                
                if (in_array($request->vnp_ResponseCode, $userCancelledCodes)) {
                    // Giữ pending - cho phép thanh toán lại
                    Log::info('[VnpayController@handleIPN] User cancelled - Keep pending', [
                        'order_id' => $order->id,
                        'vnp_ResponseCode' => $request->vnp_ResponseCode
                    ]);
                } else {
                    // Lỗi thực sự - đánh dấu failed
                    $order->update(['status' => 'failed']);

                    Log::warning('[VnpayController@handleIPN] FAILED', [
                        'order_id' => $order->id,
                        'vnp_ResponseCode' => $request->vnp_ResponseCode
                    ]);
                }

                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }

        } catch (\Exception $e) {
            Log::error('[VnpayController@handleIPN] Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['RspCode' => '99', 'Message' => 'Unknown error']);
        }
    }

    /**
     * Lấy message mô tả từ mã response VNPay
     */
    public static function getResponseMessage(string $code): string
    {
        $messages = [
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
            '09' => 'Thẻ/Tài khoản chưa đăng ký dịch vụ InternetBanking',
            '10' => 'Xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán',
            '12' => 'Thẻ/Tài khoản bị khóa',
            '13' => 'Nhập sai mật khẩu xác thực giao dịch (OTP)',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Tài khoản không đủ số dư để thực hiện giao dịch',
            '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định',
            '15' => 'Đã hết thời gian chờ thanh toán',
            '99' => 'Người dùng nhấn quay lại (back) hoặc lỗi không xác định',
        ];

        return $messages[$code] ?? 'Lỗi không xác định';
    }
}
