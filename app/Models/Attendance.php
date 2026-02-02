<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'check_in_time',
        'status'
    ];
    
    // UUID settings
    protected $keyType = 'string';
    public $incrementing = false;

    // ... (Giữ nguyên các hàm user() và company() của bạn)
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
}