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
        'balance',
    ];

    public function deposit(float $amount, ?string $paymentId = null, ?string $description = null): self
    {
        // 1. Увеличиваем баланс
        $this->balance += $amount;
        $this->save();

        // 2. Создаем запись в истории транзакций
        Transaction::create([
            'user_id' => $this->id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'status' => 'completed',
            'payment_id' => $paymentId,
            'description' => $description ?? 'Поповнення балансу',
        ]);

        return $this;
    }

    /**
     * Перевірити, чи достатньо коштів на балансі
     */
    public function hasBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Списати кошти з балансу
     */
    public function withdraw(float $amount, ?int $orderId = null, ?string $description = null): self
    {
        if (!$this->hasBalance($amount)) {
            throw new \Exception('Недостатньо коштів на балансі');
        }

        $this->balance -= $amount;
        $this->save();

        Transaction::create([
            'user_id' => $this->id,
            'order_id' => $orderId,
            'type' => 'hold',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'status' => 'completed',
            'description' => $description ?? 'Списання коштів',
        ]);

        return $this;
    }

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
