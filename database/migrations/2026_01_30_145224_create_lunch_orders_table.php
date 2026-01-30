<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('lunch_orders', function (Blueprint $table) {
        $table->id();

        // 👇 QUAN TRỌNG: Dùng foreignUuid để khớp với bảng Users
        $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

        $table->integer('price'); // Giá tiền: 25000, 30000...
        $table->string('description')->nullable();
        $table->string('status')->default('pending'); // pending, paid, failed
        $table->string('transaction_code')->nullable(); // Mã giao dịch VNPay trả về
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('lunch_orders');
}

    /**
     * Reverse the migrations.
     */
};
