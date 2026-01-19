<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'company_id', 
        'gender', 'birthday', 'phone', 'address', 'start_date', 
        'base_salary', 'status', 'avatar'
    ];

    // Ý nghĩa: 1 Nhân viên thuộc về 1 Công ty
    public function company() {
        return $this->belongsTo(Company::class);
    }

    // Ý nghĩa: 1 Nhân viên có nhiều ngày chấm công
    public function attendances() {
        return $this->hasMany(Attendance::class);
    }
}