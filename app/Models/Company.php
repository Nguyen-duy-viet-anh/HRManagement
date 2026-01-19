<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    
    // Khai báo các cột được phép thêm sửa dữ liệu
    protected $fillable = ['name', 'email', 'address', 'phone', 'standard_working_days'];

    // Ý nghĩa: 1 Công ty có nhiều nhân viên
    public function users() {
        return $this->hasMany(User::class);
    }
}