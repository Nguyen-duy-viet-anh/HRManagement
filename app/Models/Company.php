<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory; // Bắt buộc phải có để dùng Company::factory()

    protected $keyType = 'string';    // Khai báo ID là kiểu chuỗi
    public $incrementing = false;     // Tắt tự động tăng ID (vì dùng UUID)

    // Khai báo các cột được phép thêm dữ liệu hàng loạt
    protected $fillable = [
        'name', 
        'email', 
        'address', 
        'phone', 
        'standard_working_days'
    ];

    // Tự động tạo mã UUID khi tạo mới bản ghi
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Thiết lập quan hệ với bảng User (Nếu cần)
    public function users()
    {
        return $this->hasMany(User::class);
    }
}