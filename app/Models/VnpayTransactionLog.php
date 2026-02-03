<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VnpayTransactionLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_data' => 'array',
    ];

    // Các loại event
    const EVENT_PAYMENT_INITIATED = 'payment_initiated';
    const EVENT_REDIRECT_TO_VNPAY = 'redirect_to_vnpay';
    const EVENT_VNPAY_RETURN = 'vnpay_return';
    const EVENT_IPN_RECEIVED = 'ipn_received';
    const EVENT_CHECKSUM_FAILED = 'checksum_failed';
    const EVENT_ORDER_UPDATED = 'order_updated';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(LunchOrder::class, 'order_id');
    }

    /**
     * Lấy trạng thái dạng text tiếng Việt
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'success' => 'Thành công',
            'failed' => 'Thất bại',
            'pending' => 'Đang xử lý',
            default => 'Không xác định',
        };
    }

    /**
     * Lấy màu badge theo trạng thái
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Lấy số tiền thực (chia 100)
     */
    public function getRealAmountAttribute()
    {
        return $this->vnp_amount ? $this->vnp_amount / 100 : 0;
    }

    /**
     * Lấy icon theo loại event
     */
    public function getEventIconAttribute()
    {
        return match($this->event_type) {
            self::EVENT_PAYMENT_INITIATED => '🛒',
            self::EVENT_REDIRECT_TO_VNPAY => '🔗',
            self::EVENT_VNPAY_RETURN => '📥',
            self::EVENT_IPN_RECEIVED => '🔔',
            self::EVENT_CHECKSUM_FAILED => '❌',
            self::EVENT_ORDER_UPDATED => '✅',
            default => '📋',
        };
    }

    /**
     * Lấy tên event tiếng Việt
     */
    public function getEventNameAttribute()
    {
        return match($this->event_type) {
            self::EVENT_PAYMENT_INITIATED => 'Bắt đầu thanh toán',
            self::EVENT_REDIRECT_TO_VNPAY => 'Chuyển hướng đến VNPay',
            self::EVENT_VNPAY_RETURN => 'VNPay trả về kết quả',
            self::EVENT_IPN_RECEIVED => 'IPN từ VNPay (Server)',
            self::EVENT_CHECKSUM_FAILED => 'Lỗi xác thực chữ ký',
            self::EVENT_ORDER_UPDATED => 'Cập nhật đơn hàng',
            default => 'Sự kiện khác',
        };
    }

    /**
     * Lấy màu event
     */
    public function getEventColorAttribute()
    {
        return match($this->event_type) {
            self::EVENT_PAYMENT_INITIATED => 'info',
            self::EVENT_REDIRECT_TO_VNPAY => 'primary',
            self::EVENT_VNPAY_RETURN => 'warning',
            self::EVENT_IPN_RECEIVED => 'dark',
            self::EVENT_CHECKSUM_FAILED => 'danger',
            self::EVENT_ORDER_UPDATED => 'success',
            default => 'secondary',
        };
    }

    /**
     * Mô tả mã phản hồi VNPay
     */
    public function getResponseDescriptionAttribute()
    {
        $codes = [
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
            '99' => 'Lỗi không xác định',
        ];

        return $codes[$this->vnp_response_code] ?? 'Mã lỗi: ' . $this->vnp_response_code;
    }

    /**
     * Helper method để tạo log
     */
    public static function logEvent($eventType, $data = [])
    {
        return self::create(array_merge([
            'event_type' => $eventType,
            'status' => $data['status'] ?? 'pending',
        ], $data));
    }
}

