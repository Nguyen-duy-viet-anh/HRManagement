<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model lưu trữ log giao dịch OnePay
 * 
 * Dùng để:
 * - Theo dõi toàn bộ quá trình thanh toán
 * - Đối soát giao dịch khi có sự cố
 * - Debug lỗi thanh toán
 */
class OnepayTransactionLog extends Model
{
    /**
     * Không bảo vệ các field (cho phép mass assignment)
     */
    protected $guarded = [];

    /**
     * Cast các field sang kiểu dữ liệu tương ứng
     */
    protected $casts = [
        'raw_data' => 'array',  // Lưu dữ liệu gốc dạng JSON
    ];

    // ========================================
    // ĐỊNH NGHĨA CÁC LOẠI EVENT
    // ========================================
    
    /** Khởi tạo thanh toán */
    const EVENT_PAYMENT_INITIATED = 'payment_initiated';
    
    /** Chuyển hướng đến OnePay */
    const EVENT_REDIRECT_TO_ONEPAY = 'redirect_to_onepay';
    
    /** OnePay trả về qua Return URL */
    const EVENT_ONEPAY_RETURN = 'onepay_return';
    
    /** Nhận IPN từ OnePay */
    const EVENT_IPN_RECEIVED = 'ipn_received';
    
    /** Lỗi xác thực chữ ký */
    const EVENT_CHECKSUM_FAILED = 'checksum_failed';
    
    /** Đã cập nhật trạng thái đơn hàng */
    const EVENT_ORDER_UPDATED = 'order_updated';

    // ========================================
    // QUAN HỆ VỚI CÁC MODEL KHÁC
    // ========================================

    /**
     * Quan hệ với User (người thực hiện thanh toán)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ với LunchOrder (đơn hàng được thanh toán)
     */
    public function order()
    {
        return $this->belongsTo(LunchOrder::class, 'order_id');
    }

    // ========================================
    // ACCESSORS (GETTERS TÙY CHỈNH)
    // ========================================

    /**
     * Lấy trạng thái dạng text tiếng Việt
     * 
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        switch ($this->status) {
            case 'success': return 'Thành công';
            case 'failed': return 'Thất bại';
            case 'pending': return 'Đang xử lý';
            default: return 'Không xác định';
        }
    }

    /**
     * Lấy màu badge theo trạng thái (dùng cho giao diện)
     * 
     * @return string Màu CSS
     */
    public function getStatusColorAttribute(): string
    {
        switch ($this->status) {
            case 'success': return 'green';
            case 'failed': return 'red';
            case 'pending': return 'yellow';
            default: return 'gray';
        }
    }

    /**
     * Lấy tên event dạng tiếng Việt
     * 
     * @return string
     */
    public function getEventTextAttribute(): string
    {
        switch ($this->event) {
            case self::EVENT_PAYMENT_INITIATED: return 'Khởi tạo thanh toán';
            case self::EVENT_REDIRECT_TO_ONEPAY: return 'Chuyển hướng đến OnePay';
            case self::EVENT_ONEPAY_RETURN: return 'Nhận kết quả từ OnePay';
            case self::EVENT_IPN_RECEIVED: return 'Nhận thông báo IPN';
            case self::EVENT_CHECKSUM_FAILED: return 'Lỗi xác thực chữ ký';
            case self::EVENT_ORDER_UPDATED: return 'Cập nhật đơn hàng';
            default: return $this->event;
        }
    }

    /**
     * Lấy tên event để hiển thị (dùng cho view chung)
     * 
     * @return string
     */
    public function getEventDisplayAttribute(): string
    {
        return $this->event_text;
    }

    // ========================================
    // SCOPES (QUERY BUILDERS)
    // ========================================

    /**
     * Lọc theo mã đơn hàng
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Lọc theo user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Lọc giao dịch thành công
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Lọc giao dịch thất bại
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ========================================
    // STATIC METHODS (HÀM TĨNH TIỆN ÍCH)
    // ========================================

    /**
     * Ghi log sự kiện thanh toán
     * 
     * @param string      $userId     - ID người dùng (UUID)
     * @param int|null    $orderId    - ID đơn hàng
     * @param string      $event      - Loại sự kiện
     * @param string      $status     - Trạng thái (pending/success/failed)
     * @param string|null $txnRef     - Mã giao dịch OnePay
     * @param int|null    $amount     - Số tiền
     * @param string|null $responseCode - Mã phản hồi
     * @param string|null $message    - Thông báo
     * @param array       $rawData    - Dữ liệu gốc
     * 
     * @return self
     */
    public static function logEvent(
        ?string $userId,
        ?int $orderId,
        string $event,
        string $status = 'pending',
        ?string $txnRef = null,
        ?int $amount = null,
        ?string $responseCode = null,
        ?string $message = null,
        array $rawData = []
    ): self {
        return self::create([
            'user_id'       => $userId,
            'order_id'      => $orderId,
            'event'         => $event,
            'status'        => $status,
            'txn_ref'       => $txnRef,
            'amount'        => $amount,
            'response_code' => $responseCode,
            'message'       => $message,
            'raw_data'      => $rawData,
        ]);
    }

    // ========================================
    // MAPPING MÃ PHẢN HỒI ONEPAY → TIẾNG VIỆT
    // ========================================

    /**
     * Mapping mã phản hồi OnePay sang mô tả tiếng Việt dễ hiểu
     * Admin không cần hiểu kỹ thuật, chỉ cần đọc là biết vấn đề
     */
    public static function getResponseCodeMapping(): array
    {
        return [
            // === THÀNH CÔNG ===
            '0'  => ['status' => 'success', 'text' => '✅ Thanh toán thành công', 'color' => 'success', 'action' => 'Đơn hàng đã được thanh toán'],
            
            // === LỖI TỪ KHÁCH HÀNG ===
            '1'  => ['status' => 'failed', 'text' => '❌ Ngân hàng từ chối giao dịch', 'color' => 'danger', 'action' => 'Liên hệ ngân hàng để biết lý do'],
            '3'  => ['status' => 'failed', 'text' => '❌ Mã đơn vị không hợp lệ', 'color' => 'danger', 'action' => 'Kiểm tra cấu hình Merchant ID'],
            '4'  => ['status' => 'failed', 'text' => '❌ Access code không hợp lệ', 'color' => 'danger', 'action' => 'Kiểm tra cấu hình Access Code'],
            '5'  => ['status' => 'failed', 'text' => '❌ Số tiền không hợp lệ', 'color' => 'danger', 'action' => 'Kiểm tra số tiền đơn hàng'],
            '6'  => ['status' => 'failed', 'text' => '❌ Loại tiền tệ không hợp lệ', 'color' => 'danger', 'action' => 'Chỉ hỗ trợ VND'],
            '7'  => ['status' => 'failed', 'text' => '❌ Lỗi không xác định từ ngân hàng', 'color' => 'danger', 'action' => 'Thử lại hoặc chọn ngân hàng khác'],
            '8'  => ['status' => 'failed', 'text' => '❌ Lỗi định dạng dữ liệu', 'color' => 'danger', 'action' => 'Liên hệ kỹ thuật'],
            '9'  => ['status' => 'failed', 'text' => '❌ Dữ liệu bị lỗi', 'color' => 'danger', 'action' => 'Thử lại giao dịch'],
            
            // === KHÁCH HÀNG HỦY / TIMEOUT ===
            '99' => ['status' => 'pending', 'text' => '⏸️ Khách hàng hủy giao dịch', 'color' => 'warning', 'action' => 'Chờ khách hàng thanh toán lại'],
            'B'  => ['status' => 'pending', 'text' => '⏸️ Xác thực 3D-Secure thất bại', 'color' => 'warning', 'action' => 'Khách cần xác thực lại với ngân hàng'],
            'F'  => ['status' => 'pending', 'text' => '⏸️ Xác thực 3D-Secure thất bại', 'color' => 'warning', 'action' => 'Khách cần xác thực lại với ngân hàng'],
            'E'  => ['status' => 'failed', 'text' => '❌ Lỗi kết nối CSC', 'color' => 'danger', 'action' => 'Thử lại sau ít phút'],
            'Z'  => ['status' => 'failed', 'text' => '❌ Lỗi kết nối MPI', 'color' => 'danger', 'action' => 'Thử lại sau ít phút'],
            
            // === LỖI HỆ THỐNG ===
            '2'  => ['status' => 'failed', 'text' => '❌ Ngân hàng đang bảo trì', 'color' => 'danger', 'action' => 'Chờ ngân hàng hoạt động lại'],
            
            // === MẶC ĐỊNH ===
            'default' => ['status' => 'failed', 'text' => '❓ Mã lỗi không xác định', 'color' => 'secondary', 'action' => 'Liên hệ kỹ thuật để kiểm tra'],
        ];
    }

    /**
     * Lấy mô tả response code tiếng Việt
     */
    public function getResponseDescriptionAttribute(): string
    {
        $mapping = self::getResponseCodeMapping();
        $code = $this->response_code ?? 'default';
        
        return $mapping[$code]['text'] ?? $mapping['default']['text'];
    }

    /**
     * Lấy hành động cần làm dựa trên response code
     */
    public function getActionRequiredAttribute(): string
    {
        $mapping = self::getResponseCodeMapping();
        $code = $this->response_code ?? 'default';
        
        return $mapping[$code]['action'] ?? $mapping['default']['action'];
    }

    /**
     * Lấy màu badge theo response code
     */
    public function getResponseColorAttribute(): string
    {
        $mapping = self::getResponseCodeMapping();
        $code = $this->response_code ?? 'default';
        
        return $mapping[$code]['color'] ?? $mapping['default']['color'];
    }

    /**
     * Lấy icon theo loại event
     */
    public function getEventIconAttribute(): string
    {
        switch ($this->event) {
            case self::EVENT_PAYMENT_INITIATED:
                return '🛒';
            case self::EVENT_REDIRECT_TO_ONEPAY:
                return '🔗';
            case self::EVENT_ONEPAY_RETURN:
                return '📥';
            case self::EVENT_IPN_RECEIVED:
                return '🔔';
            case self::EVENT_CHECKSUM_FAILED:
                return '❌';
            case self::EVENT_ORDER_UPDATED:
                return '✅';
            default:
                return '📋';
        }
    }
}
