<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Перевірка: чи може користувач переглядати список заявок
     */
    public function viewAny(User $user)
    {
        return $user !== null;
    }

    /**
     * Перевірка: чи може користувач переглядати заявку
     */
    public function view(User $user, Order $order)
    {
        // Адмін бачить всі
        if ($user->isAdmin()) {
            return true;
        }

        // Клієнт бачить тільки свої
        if ($user->isClient() && $order->client_id === $user->id) {
            return true;
        }

        // Воркер бачить заявки, які взяв, або доступні
        if ($user->isWorker()) {
            return $order->worker_id === $user->id || $order->worker_id === null;
        }

        return false;
    }

    /**
     * Перевірка: чи може користувач створювати заявки
     */
    public function create(User $user)
    {
        // Тільки клієнти можуть створювати заявки
        return $user->isClient();
    }

    /**
     * Перевірка: чи може користувач редагувати заявку
     */
    public function update(User $user, Order $order)
    {
        // Адмін може редагувати все
        if ($user->isAdmin()) {
            return true;
        }

        // Клієнт може редагувати ТІЛЬКИ свої заявки зі статусом 'new'
        if ($user->isClient() && $order->client_id === $user->id) {
            return $order->status === 'new';
        }

        //  Воркер НЕ МОЖЕ РЕДАГУВАТИ
        return false;
    }

    /**
     * Перевірка: чи може користувач видаляти заявку
     */
    public function delete(User $user, Order $order)
    {
        // Адмін може видаляти все
        if ($user->isAdmin()) {
            return true;
        }

        // Клієнт може видаляти ТІЛЬКИ свої заявки зі статусом 'new'
        if ($user->isClient() && $order->client_id === $user->id) {
            return $order->status === 'new';
        }

        //  Воркер НЕ МОЖЕ ВИДАЛЯТИ
        return false;
    }

    /**
     * Перевірка: чи може воркер взяти заявку в роботу
     */
    public function take(User $user, Order $order)
    {
        // Тільки воркер, і тільки якщо заявка вільна
        return $user->isWorker()
            && $order->status === 'new'
            && $order->worker_id === null;
    }

    /**
     * Перевірка: чи може воркер завершити заявку
     */
    public function complete(User $user, Order $order)
    {
        // Тільки воркер, який взяв цю заявку
        return $user->isWorker()
            && $order->worker_id === $user->id
            && $order->status === 'in_progress';
    }

    /**
     * Перевірка: чи може воркер скасувати заявку
     */
    public function cancel(User $user, Order $order)
    {
        // Тільки воркер, який взяв цю заявку
        return $user->isWorker()
            && $order->worker_id === $user->id
            && $order->status === 'in_progress';
    }

    /**
     * Перевірка: чи може користувач відновити заявку (SoftDeletes)
     */
    public function restore(User $user, Order $order)
    {
        // Тільки адмін може відновлювати
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач повністю видалити заявку (SoftDeletes)
     */
    public function forceDelete(User $user, Order $order)
    {
        // Тільки адмін може повністю видаляти
        return $user->isAdmin();
    }
}
