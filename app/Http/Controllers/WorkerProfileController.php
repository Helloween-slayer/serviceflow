<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
            'avatar' => 'nullable|file|max:2048|mimes:jpg,jpeg,png,webp',
            'portfolio.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,png,zip',
            'portfolio' => 'nullable|array|max:5',
        ]);

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        // Заполняем текстовые поля
        $profile->fill($validated);

        // Загрузка аватара
        if ($request->hasFile('avatar')) {
            // Удаляем старый аватар из S3
            if ($profile->avatar) {
                Storage::disk('s3')->delete($profile->avatar);
            }

            // Сохраняем новый аватар
            $path = $request->file('avatar')->store('avatars', 's3');
            $profile->avatar = $path;
        }

        // Загрузка портфолио
        if ($request->hasFile('portfolio')) {
            // Удаляем старые файлы портфолио из S3
            if (!empty($profile->portfolio)) {
                foreach ($profile->portfolio as $oldFile) {
                    Storage::disk('s3')->delete($oldFile);
                }
            }

            // Сохраняем новые файлы
            $portfolioPaths = [];
            foreach ($request->file('portfolio') as $file) {
                $portfolioPaths[] = $file->store('portfolio', 's3');
            }
            $profile->portfolio = $portfolioPaths;
        }

        // Сохраняем профиль
        $profile->save();

        return redirect()->route('worker.profile.edit')
            ->with('success', 'Профіль успішно оновлено');
    }
}
