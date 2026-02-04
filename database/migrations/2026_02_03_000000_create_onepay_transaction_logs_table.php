<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration tạo bảng lưu log giao dịch OnePay
 * 
 * Bảng này dùng để:
 * - Theo dõi toàn bộ quá trình thanh toán
 * - Đối soát giao dịch khi có sự cố
 * - Debug lỗi thanh toán
 */
return new class extends Migration
{
    /**
     * Tạo bảng onepay_transaction_logs
     */
    public function up(): void
    {
        Schema::create('onepay_transaction_logs', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Khóa ngoại liên kết với user (UUID)
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Khóa ngoại liên kết với đơn hàng
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')
                ->references('id')
                ->on('lunch_orders')
                ->onDelete('set null');
            
            // Loại sự kiện (payment_initiated, ipn_received, etc.)
            $table->string('event', 50);
            
            // Trạng thái (pending, success, failed)
            $table->string('status', 20)->default('pending');
            
            // Mã giao dịch OnePay (vpc_MerchTxnRef)
            $table->string('txn_ref', 100)->nullable();
            
            // Số tiền (VND)
            $table->integer('amount')->nullable();
            
            // Mã phản hồi từ OnePay (vpc_TxnResponseCode)
            $table->string('response_code', 10)->nullable();
            
            // Thông báo
            $table->text('message')->nullable();
            
            // Dữ liệu gốc (lưu toàn bộ request/response)
            $table->json('raw_data')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Index để tìm kiếm nhanh
            $table->index(['order_id', 'event']);
            $table->index(['txn_ref']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Xóa bảng
     */
    public function down(): void
    {
        Schema::dropIfExists('onepay_transaction_logs');
    }
};
