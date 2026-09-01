<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkerProfileController extends Controller
{
    public function show($user)
    {
        // Если профиля нет, создаем пустую модель (заглушку), чтобы фронтенд не падал
        $profile = WorkerProfile::with(['user'])
            ->where('user_id', $user)
            ->first() ?? new WorkerProfile(['user_id' => $user]);

        $reviews = Review::with(['client'])
            ->where('worker_id', $user)
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            // Считаем общее количество отдельным запросом (не через $reviews->count(), т.к. там limit)
            'reviews_count' => Review::where('worker_id', $user)->count(),
            'average_rating' => Review::where('worker_id', $user)->avg('rating') ?? 0,
            // Если у пустой модели completed_orders = null, отдаем 0
            'completed_orders' => $profile->completed_orders ?? 0,
        ];

        return Inertia::render('Worker/Profile/Show', [
            'profile' => $profile,
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }

    public function edit()
    {
        $user = auth()->user();

        if (!$user->isWorker()) {
            abort(403, 'Тільки виконавці можуть редагувати профіль');
        }

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        return Inertia::render('Worker/Profile/Edit', [
            'profile' => $profile,
        ]);
    }

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
