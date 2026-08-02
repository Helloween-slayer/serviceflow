<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Перевірка: чи може користувач переглядати список тегів
     * Всі авторизовані користувачі можуть бачити теги
     */
    public function viewAny(User $user)
    {
        // Всі авторизовані можуть бачити теги
        return $user !== null;
    }

    /**
     * Перевірка: чи може користувач переглядати конкретний тег
     * Всі авторизовані користувачі можуть бачити теги
     */
    public function view(User $user, Tag $tag)
    {
        // Всі авторизовані можуть бачити теги
        return $user !== null;
    }

    /**
     * Перевірка: чи може користувач створювати нові теги
     * Тільки адмін може створювати теги
     */
    public function create(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач редагувати тег
     * Тільки адмін може редагувати теги
     */
    public function update(User $user, Tag $tag)
    {
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач видаляти тег
     * Тільки адмін може видаляти теги
     */
    public function delete(User $user, Tag $tag)
    {
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач відновлювати видалений тег (SoftDeletes)
     * Тільки адмін може відновлювати
     */
    public function restore(User $user, Tag $tag)
    {
        return $user->isAdmin();
    }

    /**
     * Перевірка: чи може користувач повністю видалити тег (SoftDeletes)
     * Тільки адмін може повністю видаляти
     */
    public function forceDelete(User $user, Tag $tag)
    {
        return $user->isAdmin();
    }
}
