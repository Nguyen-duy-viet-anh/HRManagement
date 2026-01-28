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
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getAvatarUrlAttribute()
{
    if ($this->avatar && filter_var($this->avatar, FILTER_VALIDATE_URL)) {
        return $this->avatar;
    }
    if ($this->avatar) {
        return asset('storage/' . $this->avatar);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random&color=fff';
}
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