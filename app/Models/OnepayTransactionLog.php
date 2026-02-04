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
        return match($this->status) {
            'success' => 'Thành công',
            'failed'  => 'Thất bại',
            'pending' => 'Đang xử lý',
            default   => 'Không xác định',
        };
    }

    /**
     * Lấy màu badge theo trạng thái (dùng cho giao diện)
     * 
     * @return string Màu CSS
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'green',
            'failed'  => 'red',
            'pending' => 'yellow',
            default   => 'gray',
        };
    }

    /**
     * Lấy tên event dạng tiếng Việt
     * 
     * @return string
     */
    public function getEventTextAttribute(): string
    {
        return match($this->event) {
            self::EVENT_PAYMENT_INITIATED  => 'Khởi tạo thanh toán',
            self::EVENT_REDIRECT_TO_ONEPAY => 'Chuyển hướng đến OnePay',
            self::EVENT_ONEPAY_RETURN      => 'Nhận kết quả từ OnePay',
            self::EVENT_IPN_RECEIVED       => 'Nhận thông báo IPN',
            self::EVENT_CHECKSUM_FAILED    => 'Lỗi xác thực chữ ký',
            self::EVENT_ORDER_UPDATED      => 'Cập nhật đơn hàng',
            default                        => $this->event,
        };
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
        string $userId,
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
}
