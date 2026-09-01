<?php

namespace App\Http\Controllers;

use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkerProfileController extends Controller
{
    /**
     * Показать профиль воркера (публичный)
     */
    public function show($userId)
    {
        $profile = WorkerProfile::with(['user'])
            ->where('user_id', $userId)
            ->firstOrFail();

        // Получаем отзывы
        $reviews = Review::with(['client'])
            ->where('worker_id', $userId)
            ->latest()
            ->limit(10)
            ->get();

        // Статистика
        $stats = [
            'reviews_count' => Review::where('worker_id', $userId)->count(),
            'average_rating' => Review::where('worker_id', $userId)->avg('rating') ?? 0,
            'completed_orders' => $profile->completed_orders ?? 0,
        ];

        return Inertia::render('Worker/Profile/Show', [
            'profile' => $profile,
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }

    /**
     * Показать форму редактирования профиля (для воркера)
     */
    public function edit()
    {
        $user = auth()->user();

        // Проверяем, что пользователь — воркер
        if (!$user->isWorker()) {
            abort(403, 'Тільки виконавці можуть редагувати профіль');
        }

        // Получаем или создаем профиль
        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        return Inertia::render('Worker/Profile/Edit', [
            'profile' => $profile,
        ]);
    }

    /**
     * Обновить профиль
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        if (!$user->isWorker()) {
            abort(403, 'Тільки виконавці можуть редагувати профіль');
        }

        $validated = $request->validate([
            'bio' => 'nullable|string|max:5000',
            'skills' => 'nullable|string|max:1000',
            'experience' => 'nullable|string|max:5000',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
        ]);

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);
        $profile->fill($validated);
        $profile->save();

        return redirect()->route('worker.profile.edit')
            ->with('success', 'Профіль успішно оновлено');
    }
}
