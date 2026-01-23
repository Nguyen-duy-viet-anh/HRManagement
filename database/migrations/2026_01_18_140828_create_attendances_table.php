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
    Schema::create('attendances', function (Blueprint $table) {
        // THAY ĐỔI: Sử dụng uuid() thay vì id()
        $table->uuid('id')->primary(); 
        
        $table->uuid('user_id'); // Đảm bảo kiểu dữ liệu khớp với bảng users
        $table->uuid('company_id');
        $table->date('date');
        $table->tinyInteger('status')->default(1);
        $table->timestamps();

        // Định nghĩa khóa ngoại (Foreign Keys)
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
