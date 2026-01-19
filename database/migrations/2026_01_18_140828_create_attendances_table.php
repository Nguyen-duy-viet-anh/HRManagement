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
        
        // SỬA: Phải dùng foreignUuid vì bảng users đã dùng UUID
        $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
        
        // GIỮ NGUYÊN: Vì bảng companies đã dùng UUID
        $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
        
        $table->date('date');
        $table->tinyInteger('status')->default(1); 
        
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
