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
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        // Quan trọng: nullable() vì Admin hệ thống ko thuộc công ty nào
        $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->tinyInteger('role')->default(2); // 0:AdminHT, 1:AdminCty, 2:NV
        
        // Thông tin cá nhân
        $table->string('avatar')->nullable();
        $table->date('birthday')->nullable();
        $table->string('phone')->nullable();
        $table->string('address')->nullable();
        $table->enum('gender', ['male', 'female', 'other'])->nullable();
        
        // Thông tin lương & việc làm
        $table->date('start_date')->nullable();
        $table->unsignedBigInteger('base_salary')->default(0);
        $table->tinyInteger('status')->default(1); // 1: Đang làm
        
        $table->rememberToken();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
