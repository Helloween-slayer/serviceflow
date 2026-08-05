<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;


    /**
     * Перевірка: чи може користувач переглядати список користувачів
     * Тільки адмін може бачити всіх користувачів
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач переглядати профіль іншого користувача
     * - Адмін бачить всіх
     * - Звичайний користувач бачить тільки свій профіль
     */
    public function view(User $user, User $model)
    {
        // Адмін бачить всіх
        if ($user->isAdmin()) {
            return true;
        }

        // Користувач бачить тільки свій профіль
        return $user->id === $model->id;
    }

    /**
     * Перевірка: чи може користувач створювати нових користувачів
     * Тільки адмін може створювати користувачів (через реєстрацію створюють самі)
     */
    public function create(User $user)
    {
        // Тільки адмін може створювати користувачів через адмінку
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач редагувати профіль
     * - Адмін може редагувати всіх
     * - Користувач може редагувати тільки свій профіль
     */
    public function update(User $user, User $model)
    {
        // Адмін може редагувати всіх
        if ($user->isAdmin()) {
            return true;
        }

        // Користувач може редагувати ТІЛЬКИ свій профіль
        return $user->id === $model->id;
    }

    /**
     * Перевірка: чи може користувач видаляти іншого користувача
     * - Тільки адмін може видаляти
     * - Не можна видаляти самого себе
     * - Не можна видаляти останнього адміна
     */
    public function delete(User $user, User $model)
    {
        // Тільки адмін може видаляти
        if (!$user->isAdmin()) {
            return false;
        }

        // Не можна видаляти самого себе
        if ($user->id === $model->id) {
            return false;
        }

        // Не можна видаляти останнього адміна
        if ($model->isAdmin()) {
            $adminCount = User::where('role_id', 1)->count();
            if ($adminCount <= 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Перевірка: чи може користувач відновлювати видаленого користувача (SoftDeletes)
     * Тільки адмін може відновлювати
     */
    public function restore(User $user, User $model)
    {
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач повністю видалити користувача (SoftDeletes)
     * Тільки адмін може повністю видаляти
     */
    public function forceDelete(User $user, User $model)
    {
        return $user->isAdmin();
    }
}
