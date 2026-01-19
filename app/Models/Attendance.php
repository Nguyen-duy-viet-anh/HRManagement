<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'status'
    ];

    // QUAN TRỌNG: Kết nối tới bảng User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Kết nối tới bảng Company (Nếu sau này bạn cần dùng $attendance->company)
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}