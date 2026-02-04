<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration thêm cột payment_method và txn_ref vào bảng lunch_orders
 * 
 * - payment_method: Lưu cổng thanh toán (vnpay, onepay)
 * - txn_ref: Mã giao dịch do hệ thống tạo (dùng để mapping với cổng thanh toán)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lunch_orders', function (Blueprint $table) {
            // Cổng thanh toán: vnpay, onepay
            $table->string('payment_method', 20)->nullable()->after('status');
            
            // Mã giao dịch nội bộ (gửi đến cổng thanh toán)
            $table->string('txn_ref', 100)->nullable()->after('payment_method');
            
            // Index để tìm kiếm nhanh theo txn_ref
            $table->index('txn_ref');
        });
    }

    public function down(): void
    {
        Schema::table('lunch_orders', function (Blueprint $table) {
            $table->dropIndex(['txn_ref']);
            $table->dropColumn(['payment_method', 'txn_ref']);
        });
    }
};
