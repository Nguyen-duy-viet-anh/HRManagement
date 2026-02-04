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
    // XỬ LÝ IPN (INSTANT PAYMENT NOTIFICATION)
    // ========================================

    /**
     * Xử lý IPN từ OnePay (Server-to-Server)
     * 
     * QUAN TRỌNG: Endpoint này KHÔNG yêu cầu authentication
     * vì được gọi từ server OnePay
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleIpn(Request $request)
    {
        // Lấy tất cả dữ liệu từ request
        $ipnData = $request->all();
        
        Log::channel('onepay')->info('Nhận IPN từ OnePay', [
            'ip'   => $request->ip(),
            'data' => $ipnData,
        ]);

        // Xử lý IPN qua service
        $result = $this->onepayService->processIpn($ipnData);

        // Phân tích mã đơn hàng từ txnRef (format: HR{order_id}_{timestamp})
        $txnRef = $result['order_id'];
        $orderId = $this->extractOrderIdFromTxnRef($txnRef);

        if ($orderId) {
            // Tìm đơn hàng trong database
            $order = LunchOrder::find($orderId);
            
            if ($order) {
                // Ghi log IPN
                OnepayTransactionLog::logEvent(
                    userId: $order->user_id,
                    orderId: $orderId,
                    event: $result['success'] 
                        ? OnepayTransactionLog::EVENT_IPN_RECEIVED 
                        : OnepayTransactionLog::EVENT_CHECKSUM_FAILED,
                    status: $result['is_paid'] ? 'success' : 'failed',
                    txnRef: $txnRef,
                    amount: $order->price,
                    responseCode: $result['response_code'],
                    message: $result['message'],
                    rawData: $ipnData
                );

                // Chỉ cập nhật đơn hàng nếu chữ ký hợp lệ
                if ($result['success']) {
                    $this->updateOrderStatus($order, $result);
                }
            }
        }

        // QUAN TRỌNG: Luôn trả về responseCode=1 nếu xử lý thành công
        // để OnePay dừng gửi IPN lặp lại
        // Trả về responseCode=0 nếu chữ ký sai
        $responseCode = $result['success'] ? '1' : '0';
        
        return response("responseCode={$responseCode}", 200)
            ->header('Content-Type', 'text/plain');
    }

    // ========================================
    // XỬ LÝ RETURN URL (TRANG KẾT QUẢ)
    // ========================================

    /**
     * Xử lý Return URL từ OnePay
     * Hiển thị kết quả thanh toán cho người dùng
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function handleReturn(Request $request)
    {
        // Lấy dữ liệu từ query string
        $returnData = $request->all();
        
        Log::channel('onepay')->info('Nhận Return URL từ OnePay', [
            'data' => $returnData,
        ]);

        // Xử lý Return qua service
        $result = $this->onepayService->processReturn($returnData);

        // Phân tích mã đơn hàng
        $txnRef = $result['order_id'];
        $orderId = $this->extractOrderIdFromTxnRef($txnRef);

        $order = null;
        if ($orderId) {
            $order = LunchOrder::find($orderId);
            
            if ($order) {
                // Ghi log Return
                OnepayTransactionLog::logEvent(
                    userId: $order->user_id,
                    orderId: $orderId,
                    event: $result['success'] 
                        ? OnepayTransactionLog::EVENT_ONEPAY_RETURN 
                        : OnepayTransactionLog::EVENT_CHECKSUM_FAILED,
                    status: $result['is_paid'] ? 'success' : 'failed',
                    txnRef: $txnRef,
                    amount: $order->price,
                    responseCode: $result['error_code'],
                    message: $result['display_text'],
                    rawData: $returnData
                );

                // Cập nhật trạng thái đơn hàng (nếu chưa được cập nhật bởi IPN)
                if ($result['success'] && $order->status === 'pending') {
                    $this->updateOrderStatus($order, $result);
                }
            }
        }

        // Trả về view hoặc redirect với kết quả
        if ($request->wantsJson()) {
            return response()->json([
                'success'      => $result['success'],
                'is_paid'      => $result['is_paid'],
                'message'      => $result['display_text'],
                'order_id'     => $orderId,
                'txn_ref'      => $txnRef,
                'onepay_txn'   => $result['txn_ref'] ?? null,
            ]);
        }

        // Redirect về trang lunch với thông báo
        if ($result['is_paid']) {
            return redirect()->route('lunch.index')
                ->with('success', 'Thanh toán thành công! ' . $result['display_text']);
        }

        return redirect()->route('lunch.index')
            ->with('error', 'Thanh toán thất bại: ' . $result['display_text']);
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

    /**
     * Cập nhật trạng thái đơn hàng
     * 
     * @param LunchOrder $order
     * @param array $result
     */
    protected function updateOrderStatus(LunchOrder $order, array $result): void
    {
        // Các mã lỗi do user hủy/back - giữ pending để thanh toán lại
        // 99: User hủy giao dịch
        // B, F: Lỗi xác thực 3D Secure (user có thể thử lại)
        $userCancelledCodes = ['99', 'B', 'F'];
        $responseCode = $result['response_code'] ?? $result['error_code'] ?? '';
        
        if (!$result['is_paid'] && in_array($responseCode, $userCancelledCodes)) {
            // Giữ pending - cho phép thanh toán lại
            Log::channel('onepay')->info("Đơn hàng #{$order->id} - User hủy/back - Giữ pending", [
                'response_code' => $responseCode,
            ]);
            
            OnepayTransactionLog::logEvent(
                userId: $order->user_id,
                orderId: $order->id,
                event: OnepayTransactionLog::EVENT_ORDER_UPDATED,
                status: 'pending',
                txnRef: $order->txn_ref,
                amount: $order->price,
                message: "User hủy giao dịch - Giữ pending để thanh toán lại"
            );
            
            return; // Không cập nhật status
        }

        $newStatus = $result['is_paid'] ? 'paid' : 'failed';
        
        $order->update([
            'status'           => $newStatus,
            'transaction_code' => $result['txn_ref'] ?? null,
        ]);

        // Ghi log cập nhật đơn hàng
        OnepayTransactionLog::logEvent(
            userId: $order->user_id,
            orderId: $order->id,
            event: OnepayTransactionLog::EVENT_ORDER_UPDATED,
            status: $newStatus === 'paid' ? 'success' : 'failed',
            txnRef: $order->txn_ref,
            amount: $order->price,
            message: "Cập nhật trạng thái đơn hàng: {$newStatus}"
        );

        Log::channel('onepay')->info("Đã cập nhật đơn hàng #{$order->id}", [
            'status' => $newStatus,
        ]);
    }
}
