<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'telegram_id',
        'telegram_notifications',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function ordersAsClient()
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function ordersAsWorker()
    {
        return $this->hasMany(Order::class, 'worker_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'client_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'worker_id');
    }

    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isWorker(): bool
    {
        return $this->role?->name === 'worker';
    }

    public function isClient(): bool
    {
        return $this->role?->name === 'client';
    }
}
