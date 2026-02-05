<?php

namespace App\Http\Controllers;

use App\Models\LunchOrder;
use App\Models\VnpayTransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * =========================================
 * VNPAY CONTROLLER - XỬ LÝ THANH TOÁN
 * =========================================
 * 
 * FLOW THANH TOÁN:
 * 1. User chọn thanh toán -> createPaymentUrl() -> Redirect tới VNPay
 * 2. User thanh toán trên VNPay
 * 3. VNPay redirect user về -> handleReturn() -> Hiển thị kết quả
 * 4. VNPay gọi server (IPN) -> handleIPN() -> Cập nhật đơn hàng
 * 
 * LƯU Ý QUAN TRỌNG:
 * - handleReturn(): CHỈ hiển thị, KHÔNG cập nhật đơn hàng (trừ localhost)
 * - handleIPN(): MỚI là nơi cập nhật đơn hàng (vì được verify bởi VNPay server)
 */
class VnpayController extends Controller
{
    /**
     * =========================================
     * BƯỚC 1: TẠO LINK THANH TOÁN
     * =========================================
     * Tạo URL để redirect user tới VNPay
     */
    public static function createPaymentUrl(LunchOrder $order, string $paymentMethod = 'VNBANK')
    {
        // Kiểm tra đơn hàng đã thanh toán chưa
        $order->refresh();
        if ($order->status === 'paid') {
            return redirect()->route('lunch.index')
                ->with('error', 'Đơn hàng đã được thanh toán trước đó.');
        }

        // Lấy cấu hình VNPay từ file .env
        $vnpUrl = env('VNP_URL');              // URL cổng thanh toán
        $vnpHashSecret = env('VNP_HASH_SECRET'); // Mã bí mật để tạo chữ ký
        $vnpTmnCode = env('VNP_TMN_CODE');      // Mã website

        // Tạo mã giao dịch duy nhất: order_id + thời gian
        // Ví dụ: 123_20260204153000
        $txnRef = $order->id . "_" . date('YmdHis'); 

        // Chuẩn bị dữ liệu gửi VNPay
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnpTmnCode,
            "vnp_Amount" => (int)$order->price * 100, // VNPay tính theo đồng * 100
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip() ?? "127.0.0.1",
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh_toan_don_" . $order->id,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => route('vnpay.return'), // URL user quay về
            "vnp_TxnRef" => $txnRef,
        ];

        // Thêm phương thức thanh toán
        if ($paymentMethod == 'VNPAYQR') {
            $inputData['vnp_BankCode'] = 'VNPAYQR';
        } elseif ($paymentMethod == 'VNBANK') {
            $inputData['vnp_BankCode'] = 'VNBANK';
        }

        // Sắp xếp theo key (bắt buộc)
        ksort($inputData);
        
        // Tạo chuỗi query
        $query = http_build_query($inputData);
        
        // Tạo URL thanh toán
        $paymentUrl = $vnpUrl . "?" . $query;
        
        // Thêm chữ ký bảo mật
        if ($vnpHashSecret) {
            $vnpSecureHash = hash_hmac('sha512', $query, $vnpHashSecret);
            $paymentUrl .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        // Ghi log: Khởi tạo thanh toán
        VnpayTransactionLog::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'event_type' => 'payment_created',
            'vnp_txn_ref' => $txnRef,
            'vnp_amount' => (int)$order->price * 100,
            'status' => 'pending',
            'description' => "Khởi tạo thanh toán cho đơn hàng #{$order->id}",
            'raw_data' => [
                'amount' => $order->price,
                'payment_method' => $paymentMethod,
            ],
        ]);

        Log::info('[VNPAY] Khởi tạo thanh toán', [
            'order_id' => $order->id,
            'txn_ref' => $txnRef,
            'amount' => $order->price
        ]);

        // Redirect tới VNPay
        return redirect($paymentUrl);
    }

    /**
     * =========================================
     * BƯỚC 2: XỬ LÝ KHI USER QUAY VỀ (RETURN URL)
     * =========================================
     * User thanh toán xong, VNPay redirect về đây
     * 
     * CHÚ Ý: Đây CHỈ để hiển thị kết quả cho user
     * KHÔNG dùng để cập nhật đơn hàng (trừ môi trường localhost)
     */
    public function handleReturn(Request $request)
    {
        $vnpTxnRef = $request->vnp_TxnRef;         // Mã giao dịch
        $vnpResponseCode = $request->vnp_ResponseCode; // Mã kết quả (00 = thành công)
        
        // Lấy order_id từ TxnRef (format: 123_20260204153000)
        $parts = explode('_', $vnpTxnRef ?? '');
        $orderId = $parts[0] ?? null;
        $order = $orderId ? LunchOrder::find($orderId) : null;

        // Ghi log: User quay về từ VNPay
        VnpayTransactionLog::create([
            'user_id' => $order?->user_id,
            'order_id' => $orderId,
            'event_type' => 'return_received',
            'vnp_txn_ref' => $vnpTxnRef,
            'vnp_response_code' => $vnpResponseCode,
            'vnp_amount' => $request->vnp_Amount,
            'status' => 'info',
            'description' => "User quay về từ VNPay (ResponseCode: {$vnpResponseCode})",
            'raw_data' => $request->all(),
        ]);

        Log::info('[VNPAY] Return URL', [
            'order_id' => $orderId,
            'response_code' => $vnpResponseCode
        ]);

        // Kiểm tra đơn hàng
        if (!$order) {
            return redirect()->route('lunch.index')
                ->with('error', 'Không tìm thấy đơn hàng.');
        }

        // Refresh để lấy status mới nhất (có thể IPN đã cập nhật)
        $order->refresh();

        // Nếu đã paid (IPN đã xử lý) -> hiển thị thành công
        if ($order->status === 'paid') {
            return redirect()->route('lunch.index')
                ->with('success', 'Thanh toán thành công!');
        }

        // ============================================
        // FALLBACK: Dùng cho môi trường LOCALHOST
        // Vì localhost không nhận được IPN từ VNPay
        // Nên phải verify và cập nhật ở đây
        // ============================================
        if ($vnpResponseCode == '00') {
            // Verify chữ ký
            $isValid = $this->verifyChecksum($request);
            
            // Kiểm tra số tiền
            $expectedAmount = $order->price * 100;
            $receivedAmount = (int)$request->vnp_Amount;
            $isAmountMatch = ($expectedAmount == $receivedAmount);
            
            if ($isValid && $isAmountMatch) {
                // Cập nhật đơn hàng
                $order->update([
                    'status' => 'paid',
                    'payment_method' => 'vnpay'
                ]);
                
                // Ghi log: Kết luận thành công
                VnpayTransactionLog::create([
                    'user_id' => $order->user_id,
                    'order_id' => $orderId,
                    'event_type' => 'conclusion',
                    'vnp_txn_ref' => $vnpTxnRef,
                    'vnp_amount' => $request->vnp_Amount,
                    'status' => 'success',
                    'description' => 'THÀNH CÔNG (Fallback - IPN chưa đến)',
                    'raw_data' => [
                        'conclusion' => 'THÀNH CÔNG',
                        'source' => 'FALLBACK (Return URL)',
                        'checksum_valid' => true,
                        'amount_match' => true,
                        'money_received' => 'CÓ - Đã thu ' . number_format($order->price) . 'đ',
                    ],
                ]);
                
                return redirect()->route('lunch.index')
                    ->with('success', 'Thanh toán thành công!');
            }
        }

        // User hủy giao dịch
        if (in_array($vnpResponseCode, ['24', '15', '99'])) {
            return redirect()->route('lunch.index')
                ->with('warning', 'Giao dịch đã bị hủy. Bạn có thể thanh toán lại.');
        }

        // Lỗi khác
        return redirect()->route('lunch.index')
            ->with('error', 'Thanh toán thất bại. Vui lòng thử lại.');
    }

    /**
     * =========================================
     * BƯỚC 3: XỬ LÝ IPN (SERVER-TO-SERVER)
     * =========================================
     * VNPay server gọi trực tiếp tới đây
     * ĐÂY MỚI LÀ NƠI CẬP NHẬT ĐƠN HÀNG CHÍNH THỨC
     * 
     * IPN = Instant Payment Notification
     */
    public function handleIPN(Request $request)
    {   
        $vnpTxnRef = $request->vnp_TxnRef;
        $vnpResponseCode = $request->vnp_ResponseCode;
        $vnpAmount = $request->vnp_Amount;
        
        Log::info('[VNPAY] Nhận IPN', [
            'txn_ref' => $vnpTxnRef,
            'response_code' => $vnpResponseCode,
            'amount' => $vnpAmount,
            'ip' => $request->ip()
        ]);

        // Lấy thông tin đơn hàng
        $parts = explode('_', $vnpTxnRef ?? '');
        $orderId = $parts[0] ?? null;
        $order = $orderId ? LunchOrder::find($orderId) : null;

        // ============================================
        // BƯỚC 1: KIỂM TRA CHỮ KÝ
        // ============================================
        $isValidChecksum = $this->verifyChecksum($request);

        if (!$isValidChecksum) {
            // Chữ ký không hợp lệ -> Có thể bị giả mạo!
            VnpayTransactionLog::create([
                'user_id' => $order?->user_id,
                'order_id' => $orderId,
                'event_type' => 'ipn_checksum_failed',
                'vnp_txn_ref' => $vnpTxnRef,
                'vnp_amount' => $vnpAmount,
                'status' => 'failed',
                'description' => 'CẢNH BÁO: Chữ ký không hợp lệ!',
                'raw_data' => $request->all(),
            ]);
            
            Log::error('[VNPAY] Checksum không hợp lệ!');
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid Checksum']);
        }

        // ============================================
        // BƯỚC 2: KIỂM TRA ĐƠN HÀNG TỒN TẠI
        // ============================================
        if (!$order) {
            Log::error('[VNPAY] Đơn hàng không tồn tại', ['order_id' => $orderId]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        // ============================================
        // BƯỚC 3: KIỂM TRA SỐ TIỀN
        // ============================================
        $vnpAmountDecimal = intval($vnpAmount) / 100;
        
        if ($vnpAmountDecimal != $order->price) {
            VnpayTransactionLog::create([
                'user_id' => $order->user_id,
                'order_id' => $orderId,
                'event_type' => 'ipn_amount_mismatch',
                'vnp_txn_ref' => $vnpTxnRef,
                'vnp_amount' => $vnpAmount,
                'status' => 'failed',
                'description' => "Số tiền không khớp: VNPay={$vnpAmountDecimal}, Order={$order->price}",
                'raw_data' => $request->all(),
            ]);
            
            Log::error('[VNPAY] Số tiền không khớp!');
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid Amount']);
        }

        // ============================================
        // BƯỚC 4: KIỂM TRA ĐÃ XỬ LÝ CHƯA
        // ============================================
        if ($order->status === 'paid') {
            Log::info('[VNPAY] Đơn hàng đã được xử lý trước đó');
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        // ============================================
        // BƯỚC 5: GHI LOG VÀ CẬP NHẬT ĐƠN HÀNG
        // ============================================
        $isSuccess = ($vnpResponseCode == '00');

        // Ghi log nhận IPN
        VnpayTransactionLog::create([
            'user_id' => $order->user_id,
            'order_id' => $orderId,
            'event_type' => 'ipn_received',
            'vnp_txn_ref' => $vnpTxnRef,
            'vnp_transaction_no' => $request->vnp_TransactionNo,
            'vnp_amount' => $vnpAmount,
            'vnp_bank_code' => $request->vnp_BankCode,
            'vnp_response_code' => $vnpResponseCode,
            'status' => $isSuccess ? 'success' : 'failed',
            'description' => $isSuccess 
                ? "IPN: Thanh toán thành công" 
                : "IPN: Thanh toán thất bại (Mã: {$vnpResponseCode})",
            'raw_data' => $request->all(),
        ]);

        if ($isSuccess) {
            // THANH TOÁN THÀNH CÔNG -> Cập nhật đơn hàng
            $order->update([
                'status' => 'paid',
                'payment_method' => 'vnpay'
            ]);
            
            // Ghi log kết luận
            VnpayTransactionLog::create([
                'user_id' => $order->user_id,
                'order_id' => $orderId,
                'event_type' => 'conclusion',
                'vnp_txn_ref' => $vnpTxnRef,
                'vnp_amount' => $vnpAmount,
                'status' => 'success',
                'description' => 'THÀNH CÔNG - Đã cập nhật đơn hàng',
                'raw_data' => [
                    'conclusion' => 'THÀNH CÔNG',
                    'checksum_valid' => true,
                    'amount_match' => true,
                    'money_received' => 'CÓ - Đã thu ' . number_format($order->price) . 'đ',
                ],
            ]);
            
            Log::info('[VNPAY] Thanh toán thành công!', ['order_id' => $orderId]);
        } else {
            // THANH TOÁN THẤT BẠI
            // Nếu user hủy (24, 15, 99) -> giữ pending để thanh toán lại
            // Lỗi khác -> đánh dấu failed
            if (!in_array($vnpResponseCode, ['24', '15', '99'])) {
                $order->update(['status' => 'failed']);
            }
            
            // Ghi log kết luận
            VnpayTransactionLog::create([
                'user_id' => $order->user_id,
                'order_id' => $orderId,
                'event_type' => 'conclusion',
                'vnp_txn_ref' => $vnpTxnRef,
                'vnp_amount' => $vnpAmount,
                'status' => 'failed',
                'description' => 'THẤT BẠI - Mã lỗi: ' . $vnpResponseCode,
                'raw_data' => [
                    'conclusion' => 'THẤT BẠI',
                    'response_code' => $vnpResponseCode,
                    'money_received' => 'KHÔNG',
                ],
            ]);
            
            Log::warning('[VNPAY] Thanh toán thất bại', [
                'order_id' => $orderId,
                'response_code' => $vnpResponseCode
            ]);
        }

        // Trả về cho VNPay biết đã xử lý xong
        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    /**
     * =========================================
     * HÀM PHỤ TRỢ: KIỂM TRA CHỮ KÝ
     * =========================================
     * Verify chữ ký từ VNPay để đảm bảo dữ liệu không bị giả mạo
     */
    private function verifyChecksum(Request $request): bool
    {
        $vnpHashSecret = config('vnpay.hash_secret');
        $vnpSecureHash = $request->vnp_SecureHash ?? '';
        
        // Lấy tất cả param bắt đầu bằng vnp_
        $inputData = [];
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        // Bỏ chữ ký ra khỏi data
        unset($inputData['vnp_SecureHash']);
        
        // Sắp xếp theo key
        ksort($inputData);
        
        // Tạo chuỗi hash
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
        
        // Tạo chữ ký từ data
        $secureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);
        
        // So sánh với chữ ký VNPay gửi về
        return ($secureHash === $vnpSecureHash);
    }
}
