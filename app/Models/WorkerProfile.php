<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'skills',
        'experience',
        'company',
        'phone',
        'location',
        'avatar',
        'rating',
        'completed_orders',
        'is_verified',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Получить рейтинг в процентах
    public function getRatingPercentAttribute(): float
    {
        return $this->rating * 20;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'worker_id', 'user_id');
    }
}
