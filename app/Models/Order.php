<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    use HasFactory, SoftDeletes;

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

    // ❌ УБЕРИ ЭТОТ БЛОК! Он конфликтует с аксессорами
    // protected function casts(): array
    // {
    //     return [
    //         'photos' => 'array',
    //         'files' => 'array',
    //     ];
    // }

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

    // ✅ Аксессор для photos как массив (с двойным декодированием)
    public function getPhotosArrayAttribute(): array
    {
        if (empty($this->photos)) {
            return [];
        }

        $decoded = json_decode($this->photos, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    // ✅ Аксессор для files как массив (с двойным декодированием)
    public function getFilesArrayAttribute(): array
    {
        if (empty($this->files)) {
            return [];
        }

        $decoded = json_decode($this->files, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    // ✅ Аксессор для URLs фото
    public function getPhotosUrlsAttribute(): array
    {
        $photos = $this->photos_array;
        return array_map(function ($path) {
            return Storage::disk('s3')->url($path);
        }, $photos);
    }

    // ✅ Аксессор для URLs файлов
    public function getFilesUrlsAttribute(): array
    {
        $files = $this->files_array;
        return array_map(function ($path) {
            return Storage::disk('s3')->url($path);
        }, $files);
    }
}
