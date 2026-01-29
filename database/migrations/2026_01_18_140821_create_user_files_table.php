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
        Schema::create('user_files', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Dùng UUID
            
            // Liên kết với bảng users
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('file_path'); // Đường dẫn file
            $table->string('original_name')->nullable(); // Tên file gốc (để hiển thị cho đẹp)
            $table->string('type')->default('document'); // Loại file (cccd, cv, hop_dong...)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_files');
    }
};
