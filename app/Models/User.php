<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str; // Đừng quên dòng này

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // --- CẤU HÌNH UUID CHO USER ---
    public $incrementing = false; // Tắt tự động tăng ID
    protected $keyType = 'string'; // Khai báo ID là kiểu chuỗi

    protected $fillable = [
        'name', 'email', 'password', 'role', 'company_id', 
        'gender', 'birthday', 'phone', 'address', 'start_date', 
        'base_salary', 'status', 'avatar'
    ];

    // Tự động tạo UUID khi tạo User mới
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    // ------------------------------

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function attendances() {
        return $this->hasMany(Attendance::class);
    }
}