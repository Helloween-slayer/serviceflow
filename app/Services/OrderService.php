<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class OrderService
{
    // ========== СТАТУСИ ==========

    public static function getActiveStatuses(): array
    {
        return Order::activeStatuses();
    }

    public static function getCompletedStatuses(): array
    {
        return Order::completedStatuses();
    }

    public static function getClientCompletedStatuses(): array
    {
        return [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
    }

    public static function getWorkerCompletedStatuses(): array
    {
        return [Order::STATUS_COMPLETED, Order::STATUS_READY];
    }

    public static function getWorkerActiveStatuses(): array
    {
        return [Order::STATUS_NEW, Order::STATUS_IN_PROGRESS];
    }

    // ========== ЗАПИТИ ==========

    public function getAvailableOrders(array $filters = [])
    {
        $query = Order::where('status', Order::STATUS_NEW)
            ->whereNull('worker_id')
            ->with(['tags', 'client']);

        // ✅ Поиск по тексту
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // ✅ Фильтр по тегам
        if (!empty($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag']);
            });
        }

        // ✅ Сортировка
        if (!empty($filters['sort'])) {
            if ($filters['sort'] === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($filters['sort'] === 'price_desc') {
                $query->orderBy('price', 'desc');
            } elseif ($filters['sort'] === 'newest') {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(10)->withQueryString();
    }

    public function getClientOrders(int $userId, ?string $status = null)
    {
        $query = Order::where('client_id', $userId)->with('tags');

        if ($status === 'completed') {
            $query->whereIn('status', self::getClientCompletedStatuses());
        } else {
            $query->whereNotIn('status', self::getClientCompletedStatuses());
        }

        return $query->paginate(10);
    }

    public function getWorkerOrders(int $userId, ?string $status = null)
    {
        $query = Order::where('worker_id', $userId)->with('tags', 'client');

        if ($status === 'completed') {
            $query->whereIn('status', self::getWorkerCompletedStatuses());
        } else {
            $query->whereIn('status', self::getWorkerActiveStatuses());
        }

        return $query->paginate(10);
    }

    public function getAllOrders(?string $status = null)
    {
        $query = Order::with('tags', 'client', 'worker');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate(10);
    }

    // ========== СТВОРЕННЯ ТА ОНОВЛЕННЯ ==========

    /**
     * Створити нову заявку
     */
    public function createOrder(array $data): Order
    {
        $order = Order::create([
            'client_id' => auth()->id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'status' => Order::STATUS_NEW,
            'photos' => !empty($data['photos']) ? json_encode($data['photos']) : null,
            'files' => !empty($data['files']) ? json_encode($data['files']) : null,
        ]);

        if (!empty($data['tags'])) {
            $order->tags()->attach($data['tags']);
        }

        return $order;
    }

    /**
     * Оновити заявку
     */
    public function updateOrder(Order $order, array $data): Order
    {
        $order->update($data);

        if (isset($data['tags'])) {
            $order->tags()->sync($data['tags']);
        } else {
            $order->tags()->detach();
        }

        return $order;
    }

    // ========== СТАТИСТИКА ==========

    public function getStats(): array
    {
        return [
            'totalOrders' => Order::count(),
            'activeOrders' => Order::whereIn('status', self::getActiveStatuses())->count(),
            'completedOrders' => Order::whereIn('status', self::getCompletedStatuses())->count(),
            'totalUsers' => User::count(),
        ];
    }

    public function getRecentOrders(int $limit = 10)
    {
        return Order::with('client')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
