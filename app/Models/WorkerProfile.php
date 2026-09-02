<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'portfolio',
        'rating',
        'completed_orders',
        'is_verified',
    ];

    protected $casts = [
        'portfolio' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRatingPercentAttribute(): float
    {
        return $this->rating * 20;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'worker_id', 'user_id');
    }

    /**
     * Получить имена файлов портфолио
     */
    public function getPortfolioNamesAttribute(): array
    {
        if (empty($this->portfolio)) {
            return [];
        }

        return array_map(function ($path) {
            return basename($path);
        }, $this->portfolio);
    }
}
