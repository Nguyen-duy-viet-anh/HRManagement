<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserFile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'file_path', 'original_name', 'type'];

    // Accessor lấy link hiển thị
    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }
}