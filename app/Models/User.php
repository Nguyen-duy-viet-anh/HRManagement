<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str; 
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use App\Models\UserFile; // Import model UserFile


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false; 
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'company_id', 
        'gender', 'birthday', 'phone', 'address', 'start_date', 
        'base_salary', 'status', 'avatar'
    ];
    
    protected $hidden = ['password', 'remember_token'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Accessor Avatar
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

    // --- CÁC MỐI QUAN HỆ ---
    
    public function files() {
        return $this->hasMany(UserFile::class, 'user_id', 'id');
    }
    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function attendances() {
        return $this->hasMany(Attendance::class);
    }

    public function vnpayLogs() {
        return $this->hasMany(VnpayTransactionLog::class);
    }

    public function lunchOrders() {
        return $this->hasMany(LunchOrder::class);
    }
    
}