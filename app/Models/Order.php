<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;



class Order extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable = [
        'client_id',
        'worker_id',
        'title',
        'description',
        'photos',
        'files',
        'price',
        'status',
        'deadline'
    ];

    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_READY = 'ready';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function activeStatuses(): array
    {
        return [self::STATUS_NEW, self::STATUS_IN_PROGRESS];
    }

    public static function completedStatuses(): array
    {
        return [self::STATUS_COMPLETED, self::STATUS_READY];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}


