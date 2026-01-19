<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        // Lưu thêm company_id để tiện thống kê
        $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
        $table->date('date');
        $table->tinyInteger('status')->default(1); // 1: Có mặt
        
        // 1 người ko thể chấm công 2 lần trong 1 ngày
        $table->unique(['user_id', 'date']);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
