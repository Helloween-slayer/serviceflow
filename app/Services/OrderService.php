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

    public static function getAvailableOrders()
    {
        return Order::where('status', Order::STATUS_NEW)
            ->whereNull('worker_id')
            ->with('tags', 'client')
            ->paginate(10);
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
