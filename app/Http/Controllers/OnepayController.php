<?php

namespace App\Http\Controllers;

use App\Models\LunchOrder;
use App\Models\OnepayTransactionLog;
use App\Services\OnepayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnepayController extends Controller
{
    /**
     * OnePay Service instance
     */
    protected OnepayService $onepayService;

    /**
     * Khởi tạo controller với dependency injection
     */
    public function __construct(OnepayService $onepayService)
    {
        $this->onepayService = $onepayService;
    }

    // ========================================
    // TẠO YÊU CẦU THANH TOÁN
    // ========================================

    /**
     * Tạo URL thanh toán và chuyển hướng đến OnePay
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'order_id'   => 'required|exists:lunch_orders,id',
            'amount'     => 'required|integer|min:1000',
            'order_info' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $orderId = $validated['order_id'];
        $amount = $validated['amount'];
        $orderInfo = $validated['order_info'] ?? "Thanh toán đơn hàng #{$orderId}";
        
        // Tạo mã giao dịch unique (prefix + order_id + timestamp)
        $txnRef = 'HR' . $orderId . '_' . time();
        
        // Lấy IP của khách hàng
        $customerIp = $request->ip();
        
        // URL trả về sau thanh toán
        $returnUrl = route('onepay.return');

        try {
            // Ghi log khởi tạo thanh toán
            OnepayTransactionLog::logEvent(
                userId: $user->id,
                orderId: $orderId,
                event: OnepayTransactionLog::EVENT_PAYMENT_INITIATED,
                status: 'pending',
                txnRef: $txnRef,
                amount: $amount,
                message: "Khởi tạo thanh toán cho đơn hàng #{$orderId}",
                rawData: [
                    'customer_ip' => $customerIp,
                    'order_info'  => $orderInfo,
                ]
            );

            // Tạo URL thanh toán OnePay
            $paymentUrl = $this->onepayService->createPaymentUrl(
                orderId: $txnRef,
                amount: $amount,
                customerIp: $customerIp,
                orderInfo: $orderInfo,
                returnUrl: $returnUrl
            );

            // Ghi log chuyển hướng
            OnepayTransactionLog::logEvent(
                userId: $user->id,
                orderId: $orderId,
                event: OnepayTransactionLog::EVENT_REDIRECT_TO_ONEPAY,
                status: 'pending',
                txnRef: $txnRef,
                amount: $amount,
                message: 'Chuyển hướng đến cổng thanh toán OnePay',
                rawData: ['payment_url' => $paymentUrl]
            );

            // Cập nhật trạng thái đơn hàng
            LunchOrder::where('id', $orderId)->update([
                'payment_status' => 'pending',
                'payment_method' => 'onepay',
                'txn_ref'        => $txnRef,
            ]);

            // Nếu là API request, trả về JSON
            if ($request->wantsJson()) {
                return response()->json([
                    'success'     => true,
                    'payment_url' => $paymentUrl,
                    'txn_ref'     => $txnRef,
                ]);
            }

            // Nếu là web request, redirect đến OnePay
            return redirect()->away($paymentUrl);

        } catch (\Exception $e) {
            Log::channel('onepay')->error('Lỗi tạo yêu cầu thanh toán', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo yêu cầu thanh toán. Vui lòng thử lại.',
                ], 500);
            }

            return back()->with('error', 'Không thể tạo yêu cầu thanh toán. Vui lòng thử lại.');
        }
    }

    // ========================================
    // XỬ LÝ IPN (Server-to-Server) - NGUỒN CHÍNH ĐỂ CẬP NHẬT ORDER
    // ========================================

    /**
     * Xử lý IPN từ OnePay (Server-to-Server)
     * 
     * ĐÂY LÀ NGUỒN CHÍNH để cập nhật đơn hàng vì:
     * - Server-to-server: Không phụ thuộc browser của user
     * - Được ký bằng secure hash: Đảm bảo dữ liệu không bị giả mạo  
     * - Retry mechanism: OnePay sẽ gửi lại nếu không nhận được response
     * 
     * Response codes cần trả về cho OnePay:
     * - responseCode=1: Xử lý thành công (OnePay dừng retry)
     * - responseCode=0: Lỗi (OnePay sẽ retry)
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleIpn(Request $request)
    {
        $ipnData = $request->all();
        $txnRef = $ipnData['vpc_MerchTxnRef'] ?? 'UNKNOWN';
        $responseCode = $ipnData['vpc_TxnResponseCode'] ?? 'N/A';
        $transactionNo = $ipnData['vpc_TransactionNo'] ?? null;
        $amount = $ipnData['vpc_Amount'] ?? 0;
        
        Log::channel('onepay')->info('[ONEPAY IPN] ===== START =====', [
            'vpc_MerchTxnRef' => $txnRef,
            'vpc_TxnResponseCode' => $responseCode,
            'vpc_TransactionNo' => $transactionNo,
            'vpc_Amount' => $amount,
            'ip' => $request->ip()
        ]);

        // Phân tích order_id từ txnRef (format: HR{order_id}_{timestamp})
        $orderId = $this->extractOrderIdFromTxnRef($txnRef);
        $order = $orderId ? LunchOrder::find($orderId) : null;

        // ========================================
        // BƯỚC 1: Xác thực chữ ký
        // ========================================
        $result = $this->onepayService->processIpn($ipnData);

        if (!$result['success']) {
            // Checksum không hợp lệ - CÓ THỂ GIẢ MẠO
            Log::channel('onepay')->error('[ONEPAY IPN] Checksum KHÔNG HỢP LỆ - CÓ THỂ GIẢ MẠO!', [
                'order_id' => $orderId,
                'ip' => $request->ip()
            ]);

            if ($order) {
                OnepayTransactionLog::logEvent(
                    userId: $order->user_id,
                    orderId: $orderId,
                    event: OnepayTransactionLog::EVENT_CHECKSUM_FAILED,
                    status: 'failed',
                    txnRef: $txnRef,
                    amount: $order->price,
                    responseCode: $responseCode,
                    message: '[IPN] CẢNH BÁO: Checksum không hợp lệ - Có thể bị giả mạo!',
                    rawData: $ipnData
                );
            }

            // Trả về 0 để OnePay biết có lỗi
            return response("responseCode=0", 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('onepay')->info('[ONEPAY IPN] Checksum hợp lệ ✓');

        // ========================================
        // BƯỚC 2: Kiểm tra Order
        // ========================================
        if (!$order) {
            Log::channel('onepay')->error('[ONEPAY IPN] Order không tồn tại', ['order_id' => $orderId]);

            OnepayTransactionLog::logEvent(
                userId: null,
                orderId: $orderId,
                event: OnepayTransactionLog::EVENT_IPN_RECEIVED,
                status: 'failed',
                txnRef: $txnRef,
                amount: (int)$amount / 100,
                responseCode: $responseCode,
                message: '[IPN] LỖI: Order không tồn tại',
                rawData: $ipnData
            );

            // Vẫn trả 1 vì đây không phải lỗi của OnePay
            return response("responseCode=1", 200)->header('Content-Type', 'text/plain');
        }

        // ========================================
        // BƯỚC 3: Kiểm tra số tiền
        // ========================================
        $amountDecimal = (int)$amount / 100;
        if ($amountDecimal > 0 && $amountDecimal != $order->price) {
            Log::channel('onepay')->error('[ONEPAY IPN] Số tiền KHÔNG KHỚP', [
                'order_id' => $orderId,
                'onepay_amount' => $amountDecimal,
                'order_price' => $order->price
            ]);

            OnepayTransactionLog::logEvent(
                userId: $order->user_id,
                orderId: $orderId,
                event: OnepayTransactionLog::EVENT_IPN_RECEIVED,
                status: 'failed',
                txnRef: $txnRef,
                amount: $amountDecimal,
                responseCode: $responseCode,
                message: "[IPN] LỖI: Số tiền không khớp (OnePay: {$amountDecimal}, Order: {$order->price})",
                rawData: $ipnData
            );

            return response("responseCode=1", 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('onepay')->info('[ONEPAY IPN] Số tiền hợp lệ ✓');

        // ========================================
        // BƯỚC 4: Kiểm tra đã xử lý chưa (tránh duplicate)
        // ========================================
        if ($order->status === 'paid') {
            Log::channel('onepay')->info('[ONEPAY IPN] Order đã được xử lý trước đó', ['order_id' => $orderId]);

            OnepayTransactionLog::logEvent(
                userId: $order->user_id,
                orderId: $orderId,
                event: OnepayTransactionLog::EVENT_IPN_RECEIVED,
                status: 'info',
                txnRef: $txnRef,
                amount: $order->price,
                responseCode: $responseCode,
                message: '[IPN] Order đã được xử lý trước đó (duplicate IPN)',
                rawData: $ipnData
            );

            return response("responseCode=1", 200)->header('Content-Type', 'text/plain');
        }

        // ========================================
        // BƯỚC 5: Ghi log IPN
        // ========================================
        $isPaid = $result['is_paid'];
        
        OnepayTransactionLog::logEvent(
            userId: $order->user_id,
            orderId: $orderId,
            event: OnepayTransactionLog::EVENT_IPN_RECEIVED,
            status: $isPaid ? 'success' : 'failed',
            txnRef: $txnRef,
            amount: $order->price,
            responseCode: $responseCode,
            message: "[IPN] OnePay thông báo: " . ($isPaid ? 'THÀNH CÔNG' : 'THẤT BẠI') . " - Mã: {$responseCode} (" . ($result['txn_response'] ?? $result['message']) . ")",
            rawData: $ipnData
        );

        // ========================================
        // BƯỚC 6: Cập nhật Order
        // ========================================
        if ($isPaid) {
            // THANH TOÁN THÀNH CÔNG
            $order->update([
                'status' => 'paid',
                'transaction_code' => $transactionNo
            ]);

            Log::channel('onepay')->info('[ONEPAY IPN] ✓ THANH TOÁN THÀNH CÔNG', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->price,
                'transaction_no' => $transactionNo
            ]);

            OnepayTransactionLog::logEvent(
                userId: $order->user_id,
                orderId: $order->id,
                event: OnepayTransactionLog::EVENT_ORDER_UPDATED,
                status: 'success',
                txnRef: $txnRef,
                amount: $order->price,
                message: "[IPN] ✓ Đơn hàng #{$order->id} đã thanh toán thành công - Mã GD: {$transactionNo}"
            );

        } else {
            // THANH TOÁN THẤT BẠI
            // Các mã do user hủy - giữ pending để thanh toán lại
            $userCancelledCodes = ['99', 'B', 'F'];
            
            if (in_array($responseCode, $userCancelledCodes)) {
                // Giữ pending
                Log::channel('onepay')->info('[ONEPAY IPN] User hủy/back - Giữ pending', [
                    'order_id' => $order->id,
                    'response_code' => $responseCode
                ]);

                OnepayTransactionLog::logEvent(
                    userId: $order->user_id,
                    orderId: $order->id,
                    event: OnepayTransactionLog::EVENT_ORDER_UPDATED,
                    status: 'pending',
                    txnRef: $txnRef,
                    amount: $order->price,
                    responseCode: $responseCode,
                    message: "[IPN] User hủy giao dịch (Mã: {$responseCode}) - Giữ pending để thanh toán lại"
                );
            } else {
                // Lỗi thực sự - đánh dấu failed
                $order->update(['status' => 'failed']);

                Log::channel('onepay')->warning('[ONEPAY IPN] ✗ THANH TOÁN THẤT BẠI', [
                    'order_id' => $order->id,
                    'response_code' => $responseCode,
                    'message' => $result['message'] ?? ''
                ]);

                OnepayTransactionLog::logEvent(
                    userId: $order->user_id,
                    orderId: $order->id,
                    event: OnepayTransactionLog::EVENT_ORDER_UPDATED,
                    status: 'failed',
                    txnRef: $txnRef,
                    amount: $order->price,
                    responseCode: $responseCode,
                    message: "[IPN] ✗ Đơn hàng #{$order->id} thất bại - Mã: {$responseCode} (" . ($result['txn_response'] ?? $result['message']) . ")"
                );
            }
        }

        Log::channel('onepay')->info('[ONEPAY IPN] ===== END =====');

        // Trả về 1 để OnePay không gửi lại
        return response("responseCode=1", 200)->header('Content-Type', 'text/plain');
    }

    // ========================================
    // XỬ LÝ RETURN URL (CHỈ HIỂN THỊ KẾT QUẢ - KHÔNG CẬP NHẬT ORDER)
    // ========================================

    /**
     * Xử lý Return URL từ OnePay
     * 
     * QUAN TRỌNG: Return URL CHỈ dùng để hiển thị kết quả cho user
     * Việc cập nhật đơn hàng được xử lý bởi IPN (handleIpn)
     * 
     * FALLBACK: Ở môi trường development (localhost), IPN không hoạt động
     * nên Return URL sẽ verify và cập nhật order nếu checksum hợp lệ.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function handleReturn(Request $request)
    {
        $returnData = $request->all();
        $txnRef = $returnData['vpc_MerchTxnRef'] ?? 'UNKNOWN';
        $responseCode = $returnData['vpc_TxnResponseCode'] ?? 'N/A';
        $transactionNo = $returnData['vpc_TransactionNo'] ?? null;
        
        Log::channel('onepay')->info('[ONEPAY RETURN] User returned from OnePay', [
            'vpc_MerchTxnRef' => $txnRef,
            'vpc_TxnResponseCode' => $responseCode,
            'ip' => $request->ip()
        ]);

        // Phân tích order_id
        $orderId = $this->extractOrderIdFromTxnRef($txnRef);
        $order = $orderId ? LunchOrder::find($orderId) : null;

        // Log: User quay về từ OnePay
        if ($order) {
            OnepayTransactionLog::logEvent(
                userId: $order->user_id,
                orderId: $orderId,
                event: OnepayTransactionLog::EVENT_ONEPAY_RETURN,
                status: 'info',
                txnRef: $txnRef,
                amount: $order->price,
                responseCode: $responseCode,
                message: "User quay về từ OnePay - Response: {$responseCode}",
                rawData: $returnData
            );
        }

        if (!$order) {
            Log::channel('onepay')->error('[ONEPAY RETURN] Order not found', ['order_id' => $orderId]);
            return redirect()->route('lunch.index')->with('error', 'Không tìm thấy đơn hàng.');
        }

        // Refresh order để lấy status mới nhất (có thể đã được IPN cập nhật)
        $order->refresh();

        // Nếu đã paid (IPN đã xử lý), chỉ hiển thị kết quả
        if ($order->status === 'paid') {
            return redirect()->route('lunch.index')->with('success', 'Thanh toán thành công!');
        }

        // ========================================
        // FALLBACK: Xử lý khi IPN chưa đến (localhost/development)
        // Verify checksum và cập nhật order nếu response_code = 0 (thành công)
        // ========================================
        if ($responseCode === '0' && $order->status !== 'paid') {
            // Verify checksum
            $result = $this->onepayService->processIpn($returnData);
            
            if ($result['success'] && $result['is_paid']) {
                // Verify số tiền
                $expectedAmount = $order->price * 100; // OnePay amount is in cents
                $receivedAmount = (int)($returnData['vpc_Amount'] ?? 0);
                
                if ($expectedAmount == $receivedAmount) {
                    // Cập nhật order (fallback khi IPN không hoạt động)
                    $order->update([
                        'status' => 'paid',
                        'payment_method' => 'onepay',
                        'transaction_code' => $transactionNo
                    ]);
                    
                    Log::channel('onepay')->info('[ONEPAY RETURN] FALLBACK: Cập nhật order từ Return URL', [
                        'order_id' => $orderId,
                        'note' => 'IPN chưa đến, đã verify checksum và cập nhật order'
                    ]);
                    
                    // Log order updated
                    OnepayTransactionLog::logEvent(
                        userId: $order->user_id,
                        orderId: $orderId,
                        event: OnepayTransactionLog::EVENT_ORDER_UPDATED,
                        status: 'success',
                        txnRef: $txnRef,
                        amount: $order->price,
                        responseCode: $responseCode,
                        message: '[FALLBACK] Cập nhật đơn hàng từ Return URL (IPN chưa đến)',
                        rawData: $returnData
                    );
                    
                    return redirect()->route('lunch.index')->with('success', 'Thanh toán thành công!');
                } else {
                    Log::channel('onepay')->warning('[ONEPAY RETURN] Số tiền không khớp', [
                        'expected' => $expectedAmount,
                        'received' => $receivedAmount
                    ]);
                }
            } else {
                Log::channel('onepay')->warning('[ONEPAY RETURN] Checksum không hợp lệ', [
                    'order_id' => $orderId
                ]);
            }
            
            // Checksum invalid hoặc amount không khớp - đợi IPN
            return redirect()->route('lunch.index')
                ->with('warning', 'Giao dịch đang được xử lý. Vui lòng đợi trong giây lát.');
        }

        // User hủy hoặc lỗi
        $userCancelledCodes = ['99', 'B', 'F'];
        if (in_array($responseCode, $userCancelledCodes)) {
            return redirect()->route('lunch.index')
                ->with('warning', 'Giao dịch đã bị hủy. Bạn có thể thanh toán lại.');
        }

        // Các lỗi khác
        return redirect()->route('lunch.index')
            ->with('error', 'Thanh toán thất bại. Vui lòng thử lại.');
    }

    // ========================================
    // HÀM TIỆN ÍCH NỘI BỘ
    // ========================================

    /**
     * Trích xuất order_id từ txnRef
     * 
     * TxnRef format: HR{order_id}_{timestamp}
     * Ví dụ: HR123_1706947200 -> 123
     * 
     * @param string $txnRef
     * @return int|null
     */
    protected function extractOrderIdFromTxnRef(string $txnRef): ?int
    {
        // Pattern: HR{order_id}_{timestamp}
        if (preg_match('/^HR(\d+)_\d+$/', $txnRef, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
}
