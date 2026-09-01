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

        // Генерируем URL для файлов
        $profileData = $profile->toArray();
        $profileData['avatar_url'] = $profile->avatar ? Storage::disk('s3')->temporaryUrl($profile->avatar, now()->addMinutes(60)) : null;

        // Портфолио
        $portfolioPaths = $this->decodeJsonField($profile->portfolio);
        $profileData['portfolio_urls'] = !empty($portfolioPaths) ? array_map(function ($path) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
        }, $portfolioPaths) : [];
        $profileData['portfolio'] = $portfolioPaths;

        return Inertia::render('Worker/Profile/Show', [
            'profile' => $profileData,
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

        if (!$user->isWorker()) {
            abort(403, 'Тільки виконавці можуть редагувати профіль');
        }

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        // Генерируем URL для файлов
        $profileData = $profile->toArray();
        $profileData['avatar_url'] = $profile->avatar ? Storage::disk('s3')->temporaryUrl($profile->avatar, now()->addMinutes(60)) : null;

        $portfolioPaths = $this->decodeJsonField($profile->portfolio);
        $profileData['portfolio_urls'] = !empty($portfolioPaths) ? array_map(function ($path) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
        }, $portfolioPaths) : [];
        $profileData['portfolio'] = $portfolioPaths;

        return Inertia::render('Worker/Profile/Edit', [
            'profile' => $profileData,
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
            'removed_portfolio' => 'nullable|array',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        // Заполняем текстовые поля
        $profile->fill($validated);

        // Загрузка аватара
        if ($request->hasFile('avatar')) {
            if ($profile->avatar) {
                Storage::disk('s3')->delete($profile->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 's3');
            $profile->avatar = $path;
        }

        // Удаление аватара
        if ($request->input('remove_avatar') === 'true' || $request->input('remove_avatar') === '1') {
            if ($profile->avatar) {
                Storage::disk('s3')->delete($profile->avatar);
                $profile->avatar = null;
            }
        }

        // Портфолио - получаем существующие
        $existingPortfolio = $this->decodeJsonField($profile->portfolio);

        // Удаляем отмеченные файлы
        if ($request->has('removed_portfolio')) {
            $removedFiles = $request->input('removed_portfolio');
            foreach ($removedFiles as $path) {
                Storage::disk('s3')->delete($path);
                $existingPortfolio = array_values(array_diff($existingPortfolio, [$path]));
            }
        }

        // Добавляем новые файлы
        if ($request->hasFile('portfolio')) {
            foreach ($request->file('portfolio') as $file) {
                $existingPortfolio[] = $file->store('portfolio', 's3');
            }
        }

        $profile->portfolio = !empty($existingPortfolio) ? json_encode($existingPortfolio) : null;

        // Сохраняем профиль
        $profile->save();

        return redirect()->route('worker.profile.edit')
            ->with('success', 'Профіль успішно оновлено');
    }

    /**
     * Вспомогательный метод для декодирования JSON
     */
    private function decodeJsonField($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
