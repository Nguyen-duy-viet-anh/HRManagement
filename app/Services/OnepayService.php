<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * OnePay Payment Gateway Service
 * 
 * Service class để xử lý thanh toán qua cổng OnePay
 * Hỗ trợ: Tạo yêu cầu thanh toán, xử lý IPN, xử lý Return URL
 * 
 * @author HRManagement Team
 */
class OnepayService
{
    // ========================================
    // CẤU HÌNH ONEPAY
    // ========================================
    
    /**
     * URL cổng thanh toán OnePay
     * Sandbox: https://mtf.onepay.vn/paygate/vpcpay.op
     * Production: https://onepay.vn/paygate/vpcpay.op
     */
    protected string $paymentUrl;
    
    /**
     * Mã Merchant được OnePay cấp
     */
    protected string $merchantId;
    
    /**
     * Access Code được OnePay cấp
     */
    protected string $accessCode;
    
    /**
     * Secret Key để tạo chữ ký HMAC SHA256
     * Lưu ý: Key này ở dạng HEX
     */
    protected string $secureSecret;

    /**
     * Khởi tạo service với cấu hình từ config/services.php
     */
    public function __construct()
    {
        $this->paymentUrl = config('services.onepay.payment_url');
        $this->merchantId = config('services.onepay.merchant_id');
        $this->accessCode = config('services.onepay.access_code');
        $this->secureSecret = config('services.onepay.secure_secret');
    }

    // ========================================
    // TẠO YÊU CẦU THANH TOÁN (PAYMENT REQUEST)
    // ========================================

    /**
     * Tạo URL thanh toán OnePay
     * 
     * @param string $orderId      - Mã đơn hàng (unique)
     * @param int    $amount       - Số tiền (VND, chưa nhân 100)
     * @param string $customerIp   - IP của khách hàng
     * @param string $orderInfo    - Thông tin đơn hàng
     * @param string $returnUrl    - URL trả về sau thanh toán
     * 
     * @return string URL thanh toán đầy đủ
     */
    public function createPaymentUrl(
        string $orderId,
        int $amount,
        string $customerIp,
        string $orderInfo,
        string $returnUrl
    ): string {
        // Tạo mảng tham số thanh toán theo chuẩn OnePay
        $params = $this->buildPaymentParams($orderId, $amount, $customerIp, $orderInfo, $returnUrl);
        
        // Tạo chữ ký bảo mật
        $secureHash = $this->createSecureHash($params);
        $params['vpc_SecureHash'] = $secureHash;
        
        // Tạo URL hoàn chỉnh
        $paymentUrl = $this->paymentUrl . '?' . http_build_query($params);
        
        // Ghi log để đối soát khi có lỗi
        $this->logPaymentRequest($orderId, $params, $paymentUrl);
        
        return $paymentUrl;
    }

    /**
     * Xây dựng mảng tham số thanh toán
     * 
     * @param string $orderId    - Mã đơn hàng
     * @param int    $amount     - Số tiền (VND)
     * @param string $customerIp - IP khách hàng
     * @param string $orderInfo  - Thông tin đơn hàng
     * @param string $returnUrl  - URL trả về
     * 
     * @return array Mảng tham số
     */
    protected function buildPaymentParams(
        string $orderId,
        int $amount,
        string $customerIp,
        string $orderInfo,
        string $returnUrl
    ): array {
        // Xử lý IP: Nếu là localhost IPv6 (::1) thì đổi thành 127.0.0.1
        // OnePay không chấp nhận IPv6
        if ($customerIp === '::1' || empty($customerIp)) {
            $customerIp = '127.0.0.1';
        }
        
        return [
            // Thông số bắt buộc theo tài liệu OnePay
            'vpc_Version'     => '2',                    // Phiên bản API
            'vpc_Command'     => 'pay',                  // Loại lệnh: thanh toán
            'vpc_Currency'    => 'VND',                  // Loại tiền tệ
            'vpc_Locale'      => 'vn',                   // Ngôn ngữ hiển thị
            
            // Thông tin Merchant
            'vpc_Merchant'    => $this->merchantId,      // Mã Merchant
            'vpc_AccessCode'  => $this->accessCode,      // Access Code
            
            // Thông tin đơn hàng
            'vpc_MerchTxnRef' => $orderId,               // Mã tham chiếu đơn hàng (unique)
            'vpc_OrderInfo'   => $orderInfo,             // Thông tin đơn hàng
            'vpc_Amount'      => $amount * 100,          // Số tiền (nhân 100 theo yêu cầu OnePay)
            
            // Thông tin khách hàng
            'vpc_TicketNo'    => $customerIp,            // IP của khách hàng
            
            // URL callback
            'vpc_ReturnURL'   => $returnUrl,             // URL trả về sau thanh toán
            
            // Ép vào trang nội địa (theo yêu cầu)
            'vpc_CardList'    => 'DOMESTIC',             // Chỉ cho phép thẻ nội địa
        ];
    }

    // ========================================
    // XỬ LÝ CHỮ KÝ BẢO MẬT (SECURE HASH)
    // ========================================

    /**
     * Tạo chữ ký bảo mật HMAC SHA256
     * 
     * Quy trình:
     * 1. Lọc các tham số có tiền tố vpc_ và có giá trị
     * 2. Sắp xếp theo thứ tự bảng chữ cái (A-Z)
     * 3. Tạo chuỗi query string
     * 4. Hash bằng HMAC SHA256 với secret key dạng hex
     * 5. Chuyển kết quả thành chữ IN HOA
     * 
     * @param array $params - Mảng tham số cần tạo chữ ký
     * 
     * @return string Chữ ký dạng HEX viết hoa
     */
    public function createSecureHash(array $params): string
    {
        // Bước 1: Lọc và sắp xếp tham số
        $hashData = $this->prepareHashData($params);
        
        // Bước 2: Tạo chuỗi để hash
        $stringToHash = $this->buildHashString($hashData);
        
        // Log chuỗi trước khi hash để debug
        Log::channel('onepay')->debug('Chuỗi dữ liệu trước khi hash', [
            'string_to_hash' => $stringToHash,
        ]);
        
        // Bước 3: Hash bằng HMAC SHA256
        // Lưu ý: Secret key phải được chuyển từ HEX sang binary
        $secureHash = $this->hmacSha256($stringToHash);
        
        return $secureHash;
    }

    /**
     * Chuẩn bị dữ liệu để tạo hash
     * - Lấy các tham số có tiền tố vpc_ và user_ (theo tài liệu OnePay)
     * - Loại bỏ các tham số rỗng và vpc_SecureHash
     * - Sắp xếp theo thứ tự bảng chữ cái (phân biệt chữ hoa/thường)
     * 
     * @param array $params - Mảng tham số gốc
     * 
     * @return array Mảng tham số đã được chuẩn bị
     */
    protected function prepareHashData(array $params): array
    {
        $hashData = [];
        
        foreach ($params as $key => $value) {
            // Chỉ lấy tham số có tiền tố vpc_ hoặc user_ và không phải vpc_SecureHash
            $isVpcParam = str_starts_with($key, 'vpc_') && $key !== 'vpc_SecureHash';
            $isUserParam = str_starts_with($key, 'user_');
            
            if ($isVpcParam || $isUserParam) {
                // Chỉ lấy các tham số có giá trị (không rỗng)
                if ($value !== '' && $value !== null) {
                    $hashData[$key] = $value;
                }
            }
        }
        
        // Sắp xếp theo thứ tự bảng chữ cái (A-Z), phân biệt chữ hoa/thường
        ksort($hashData);
        
        return $hashData;
    }

    /**
     * Xây dựng chuỗi để hash từ mảng tham số
     * Format: key1=value1&key2=value2&...
     * 
     * Sử dụng http_build_query + urldecode để đảm bảo tương thích
     * với cách OnePay xử lý dữ liệu (giá trị gốc không URL encode)
     * 
     * @param array $hashData - Mảng tham số đã được sắp xếp
     * 
     * @return string Chuỗi query string
     */
    protected function buildHashString(array $hashData): string
    {
        // Dùng http_build_query để tạo chuỗi, sau đó urldecode để lấy giá trị gốc
        // Điều này đảm bảo đúng format và xử lý các ký tự đặc biệt
        return urldecode(http_build_query($hashData));
    }

    /**
     * Tạo hash HMAC SHA256
     * 
     * @param string $data - Chuỗi cần hash
     * 
     * @return string Hash dạng HEX viết hoa
     */
    protected function hmacSha256(string $data): string
    {
        // Chuyển đổi secret key từ HEX sang binary
        // Đây là bước quan trọng vì OnePay yêu cầu key dạng hex
        $secretKeyBinary = pack('H*', $this->secureSecret);
        
        // Tạo HMAC SHA256 hash
        $hash = hash_hmac('sha256', $data, $secretKeyBinary);
        
        // Chuyển thành chữ IN HOA theo yêu cầu
        return strtoupper($hash);
    }

    // ========================================
    // XÁC THỰC CHỮ KÝ (VERIFY SIGNATURE)
    // ========================================

    /**
     * Xác thực chữ ký từ response của OnePay
     * 
     * Dùng cho cả IPN và Return URL
     * 
     * @param array $responseData - Dữ liệu nhận được từ OnePay
     * 
     * @return bool True nếu chữ ký hợp lệ
     */
    public function verifySecureHash(array $responseData): bool
    {
        // Lấy chữ ký từ response
        $receivedHash = $responseData['vpc_SecureHash'] ?? '';
        
        if (empty($receivedHash)) {
            Log::channel('onepay')->warning('Không tìm thấy vpc_SecureHash trong response');
            return false;
        }
        
        // Tính toán lại chữ ký từ các tham số
        $calculatedHash = $this->createSecureHash($responseData);
        
        // So sánh 2 chữ ký (case-insensitive để an toàn)
        $isValid = hash_equals(strtoupper($receivedHash), strtoupper($calculatedHash));
        
        // Log kết quả xác thực
        Log::channel('onepay')->info('Kết quả xác thực chữ ký', [
            'received_hash'   => $receivedHash,
            'calculated_hash' => $calculatedHash,
            'is_valid'        => $isValid,
        ]);
        
        return $isValid;
    }

    // ========================================
    // XỬ LÝ IPN (INSTANT PAYMENT NOTIFICATION)
    // ========================================

    /**
     * Xử lý IPN từ OnePay (Server-to-Server)
     * 
     * Đây là phần QUAN TRỌNG NHẤT để cập nhật trạng thái đơn hàng
     * khi người dùng đóng trình duyệt trước khi return về website
     * 
     * @param array $ipnData - Dữ liệu IPN từ OnePay
     * 
     * @return array [
     *     'success'       => bool,      // Xử lý thành công hay không
     *     'message'       => string,    // Thông báo
     *     'order_id'      => string,    // Mã đơn hàng
     *     'response_code' => string,    // Mã phản hồi từ OnePay
     *     'is_paid'       => bool,      // Đơn hàng đã thanh toán thành công
     * ]
     */
    public function processIpn(array $ipnData): array
    {
        $orderId = $ipnData['vpc_MerchTxnRef'] ?? 'UNKNOWN';
        $responseCode = $ipnData['vpc_TxnResponseCode'] ?? 'N/A';
        
        // Bước 1: Xác thực chữ ký
        $isHashValid = $this->verifySecureHash($ipnData);
        
        // Log chi tiết theo yêu cầu
        Log::channel('onepay')->info("Nhận IPN cho đơn hàng [{$orderId}] - Trạng thái: [{$responseCode}] - Hash hợp lệ: [" . ($isHashValid ? 'True' : 'False') . "]");
        
        // Bước 2: Xử lý logic theo kết quả xác thực
        if (!$isHashValid) {
            // CẢNH BÁO: Có thể bị giả mạo!
            Log::channel('onepay')->warning("CẢNH BÁO GIẢ MẠO - IPN cho đơn hàng [{$orderId}]", [
                'ip'   => request()->ip(),
                'data' => $ipnData,
            ]);
            
            return [
                'success'       => false,
                'message'       => 'Chữ ký không hợp lệ - Cảnh báo giả mạo',
                'order_id'      => $orderId,
                'response_code' => '0', // Phản hồi 0 khi chữ ký sai
                'is_paid'       => false,
            ];
        }
        
        // Chữ ký hợp lệ, kiểm tra trạng thái thanh toán
        $isPaid = ($responseCode === '0'); // 0 = Giao dịch thành công
        
        return [
            'success'       => true,
            'message'       => $isPaid ? 'Thanh toán thành công' : 'Thanh toán thất bại',
            'order_id'      => $orderId,
            'response_code' => $responseCode,
            'is_paid'       => $isPaid,
            'txn_response'  => $this->getTxnResponseMessage($responseCode),
        ];
    }

    // ========================================
    // XỬ LÝ RETURN URL (TRANG KẾT QUẢ)
    // ========================================

    /**
     * Xử lý dữ liệu từ Return URL
     * Dùng để hiển thị kết quả thanh toán cho người dùng
     * 
     * @param array $returnData - Dữ liệu từ query string
     * 
     * @return array Kết quả xử lý
     */
    public function processReturn(array $returnData): array
    {
        $orderId = $returnData['vpc_MerchTxnRef'] ?? 'UNKNOWN';
        $responseCode = $returnData['vpc_TxnResponseCode'] ?? 'N/A';
        
        // Xác thực chữ ký
        $isHashValid = $this->verifySecureHash($returnData);
        
        Log::channel('onepay')->info("Return URL cho đơn hàng [{$orderId}] - Trạng thái: [{$responseCode}] - Hash hợp lệ: [" . ($isHashValid ? 'True' : 'False') . "]");
        
        if (!$isHashValid) {
            return [
                'success'      => false,
                'message'      => 'Dữ liệu không hợp lệ',
                'order_id'     => $orderId,
                'is_paid'      => false,
                'error_code'   => 'INVALID_HASH',
                'display_text' => 'Có lỗi xảy ra trong quá trình xác thực thanh toán. Vui lòng liên hệ hỗ trợ.',
            ];
        }
        
        $isPaid = ($responseCode === '0');
        
        return [
            'success'      => true,
            'message'      => $isPaid ? 'Thanh toán thành công' : 'Thanh toán thất bại',
            'order_id'     => $orderId,
            'is_paid'      => $isPaid,
            'error_code'   => $isPaid ? null : $responseCode,
            'display_text' => $this->getTxnResponseMessage($responseCode),
            'txn_ref'      => $returnData['vpc_TransactionNo'] ?? null,
        ];
    }

    // ========================================
    // HÀM TIỆN ÍCH
    // ========================================

    /**
     * Lấy thông báo lỗi theo mã response của OnePay
     * 
     * @param string $code - Mã response
     * 
     * @return string Thông báo tiếng Việt
     */
    public function getTxnResponseMessage(string $code): string
    {
        $messages = [
            '0'  => 'Giao dịch thành công',
            '1'  => 'Ngân hàng từ chối giao dịch',
            '2'  => 'Ngân hàng phát hành thẻ từ chối giao dịch',
            '3'  => 'Không nhận được kết quả trả về từ ngân hàng phát hành thẻ',
            '4'  => 'Thẻ hết hạn hoặc sai ngày hết hạn',
            '5'  => 'Không đủ số dư/hạn mức để thanh toán',
            '6'  => 'Lỗi từ ngân hàng phát hành thẻ',
            '7'  => 'Lỗi khi thanh toán',
            '8'  => 'Ngân hàng phát hành thẻ không hỗ trợ thanh toán trực tuyến',
            '9'  => 'Tên chủ thẻ/tài khoản không hợp lệ',
            '10' => 'Thẻ hết hạn/Thẻ bị khóa',
            '11' => 'Thẻ/Tài khoản chưa đăng ký dịch vụ thanh toán trực tuyến',
            '12' => 'Ngày phát hành/Hết hạn không hợp lệ',
            '13' => 'Vượt quá hạn mức thanh toán trong ngày',
            '21' => 'Số tiền không đủ để thanh toán',
            '99' => 'Người dùng hủy giao dịch',
            'B'  => 'Lỗi xác thực 3D Secure',
            'E'  => 'Lỗi CSC (Card Security Code)',
            'F'  => 'Lỗi xác thực 3D Secure',
            'Z'  => 'Giao dịch bị từ chối',
        ];

        return $messages[$code] ?? "Lỗi không xác định (Mã: {$code})";
    }

    /**
     * Ghi log yêu cầu thanh toán
     * 
     * @param string $orderId    - Mã đơn hàng
     * @param array  $params     - Tham số thanh toán
     * @param string $paymentUrl - URL thanh toán hoàn chỉnh
     */
    protected function logPaymentRequest(string $orderId, array $params, string $paymentUrl): void
    {
        // Ẩn thông tin nhạy cảm trước khi log
        $safeParams = $params;
        unset($safeParams['vpc_SecureHash']);
        
        Log::channel('onepay')->info("Tạo yêu cầu thanh toán cho đơn hàng [{$orderId}]", [
            'params'      => $safeParams,
            'payment_url' => $paymentUrl,
        ]);
    }

    /**
     * Kiểm tra xem môi trường hiện tại có phải sandbox không
     * 
     * @return bool
     */
    public function isSandbox(): bool
    {
        return str_contains($this->paymentUrl, 'mtf.onepay.vn');
    }
}
