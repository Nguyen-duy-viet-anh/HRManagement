<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * =========================================
 * MODEL LOG GIAO DỊCH VNPAY
 * =========================================
 * 
 * Lưu lại toàn bộ quá trình thanh toán:
 * - Khởi tạo giao dịch
 * - User quay về (return)
 * - Nhận IPN từ VNPay
 * - Kết luận cuối cùng
 */
class VnpayTransactionLog extends Model
{
    // Cho phép insert tất cả các field
    protected $guarded = [];

    // Tự động convert raw_data từ JSON sang array
    protected $casts = [
        'raw_data' => 'array',
    ];

    // =========================================
    // QUAN HỆ VỚI CÁC MODEL KHÁC
    // =========================================

    /**
     * Lấy thông tin người dùng
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lấy thông tin đơn hàng
     */
    public function order()
    {
        return $this->belongsTo(LunchOrder::class, 'order_id');
    }

    // =========================================
    // CÁC HÀM LẤY TEXT HIỂN THỊ
    // =========================================

    /**
     * Lấy tên sự kiện tiếng Việt
     */
    public function getEventNameAttribute()
    {
        $names = [
            'payment_created' => 'Khởi tạo thanh toán',
            'return_received' => 'User quay về từ VNPay',
            'ipn_received' => 'Nhận IPN từ VNPay',
            'ipn_checksum_failed' => 'Lỗi chữ ký',
            'ipn_amount_mismatch' => 'Số tiền không khớp',
            'conclusion' => 'Kết luận',
        ];
        
        return $names[$this->event_type] ?? $this->event_type;
    }

    /**
     * Lấy số tiền thực (VNPay tính đơn vị x100)
     */
    public function getRealAmountAttribute()
    {
        return $this->vnp_amount ? $this->vnp_amount / 100 : 0;
    }

    /**
     * Lấy màu theo trạng thái
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'success' => 'success',  // Xanh lá
            'failed' => 'danger',    // Đỏ
            'pending' => 'warning',  // Vàng
            'info' => 'primary',     // Xanh dương
        ];
        
        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Giải thích mã lỗi VNPay
     */
    public function getResponseDescriptionAttribute()
    {
        $codes = [
            '00' => 'Thành công',
            '07' => 'Thành công (cần kiểm tra)',
            '09' => 'Chưa đăng ký Internet Banking',
            '10' => 'Sai thông tin thẻ quá 3 lần',
            '11' => 'Hết thời gian thanh toán',
            '12' => 'Thẻ bị khóa',
            '13' => 'Sai mã OTP',
            '24' => 'Khách hủy giao dịch',
            '51' => 'Không đủ số dư',
            '65' => 'Vượt hạn mức ngày',
            '75' => 'Ngân hàng bảo trì',
            '79' => 'Sai mật khẩu quá nhiều lần',
            '99' => 'Lỗi không xác định',
        ];

        return $codes[$this->vnp_response_code] ?? 'Mã: ' . $this->vnp_response_code;
    }
}

