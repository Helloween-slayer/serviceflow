<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkerProfilePolicy
{
    use HandlesAuthorization;

    public function view(?User $user, WorkerProfile $workerProfile)
    {
        return true;
    }

    public function update(User $user, WorkerProfile $workerProfile)
    {
        // Адмін може редагувати всі профілі
        if ($user->isAdmin()) {
            return true;
        }

        // Воркер може редагувати тільки свій профіль
        // ✅ ВИКОРИСТОВУЙ == ЗАМІСТЬ ===
        return $user->isWorker() && $user->id === $workerProfile->user_id;
    }

    public function delete(User $user, WorkerProfile $workerProfile)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isWorker() && $user->id == $workerProfile->user_id;
    }

    public function create(User $user)
    {
        return $user->isWorker();
    }
}
