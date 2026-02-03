<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vnpay_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('vnp_txn_ref')->nullable(); // Mã tham chiếu giao dịch
            $table->string('vnp_transaction_no')->nullable(); // Mã giao dịch VNPay
            $table->bigInteger('vnp_amount')->nullable(); // Số tiền (đã x100)
            $table->string('vnp_bank_code')->nullable(); // Mã ngân hàng
            $table->string('vnp_bank_tran_no')->nullable(); // Mã giao dịch ngân hàng
            $table->string('vnp_card_type')->nullable(); // Loại thẻ
            $table->string('vnp_order_info')->nullable(); // Thông tin đơn hàng
            $table->string('vnp_pay_date')->nullable(); // Ngày thanh toán
            $table->string('vnp_response_code')->nullable(); // Mã phản hồi (00 = thành công)
            $table->string('vnp_tmn_code')->nullable(); // Mã website
            $table->string('vnp_transaction_status')->nullable(); // Trạng thái giao dịch
            $table->string('status')->default('unknown'); // success, failed, pending
            $table->string('event_type')->default('vnpay_return'); // payment_initiated, redirect_to_vnpay, vnpay_return, checksum_failed
            $table->string('session_id')->nullable(); // Để nhóm các log cùng 1 phiên thanh toán
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->text('raw_data')->nullable(); // Dữ liệu thô từ VNPay
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('lunch_orders')->onDelete('set null');
            $table->index('vnp_txn_ref');
            $table->index('vnp_transaction_no');
            $table->index('status');
            $table->index('session_id');
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vnpay_transaction_logs');
    }
};
