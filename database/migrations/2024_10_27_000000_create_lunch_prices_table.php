<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lunch_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('price')->unique(); // Giá tiền
            $table->timestamps();
        });

        // Thêm dữ liệu mặc định ban đầu
        DB::table('lunch_prices')->insert([
            ['price' => 25000, 'created_at' => now(), 'updated_at' => now()],
            ['price' => 30000, 'created_at' => now(), 'updated_at' => now()],
            ['price' => 35000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('lunch_prices');
    }
};