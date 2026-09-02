<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class WorkerProfileController extends Controller
{
    public function show($user)
    {
        $userId = (int)$user;

        $profile = WorkerProfile::with(['user'])
            ->where('user_id', $userId)
            ->first();

        if (!$profile) {
            $profile = new WorkerProfile(['user_id' => $userId]);
            $profile->setRelation('user', \App\Models\User::find($userId));
        }

        $reviews = Review::with(['client'])
            ->where('worker_id', $userId)
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'reviews_count' => Review::where('worker_id', $userId)->count(),
            'average_rating' => Review::where('worker_id', $userId)->avg('rating') ?? 0,
            'completed_orders' => $profile->completed_orders ?? 0,
        ];

        $canEdit = auth()->check() && Gate::allows('update', $profile);

        $profileData = $profile->toArray();

        // ✅ ЯК В ORDER CONTROLLER - ВИКОРИСТОВУЄМО temporaryUrl
        $profileData['avatar_url'] = $profile->avatar
            ? Storage::disk('s3')->temporaryUrl($profile->avatar, now()->addMinutes(60))
            : null;

        // ✅ ДЛЯ ПОРТФОЛІО ТЕЖ РОБИМО temporaryUrl
        if (!empty($profile->portfolio) && is_array($profile->portfolio)) {
            $profileData['portfolio_urls'] = array_map(function ($path) {
                try {
                    return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
                } catch (\Exception $e) {
                    \Log::error('Error generating portfolio URL: ' . $e->getMessage());
                    return null;
                }
            }, $profile->portfolio);
        } else {
            $profileData['portfolio_urls'] = [];
        }

        \Log::info('Worker Profile Show:', [
            'user_id' => $userId,
            'profile_id' => $profile->id,
            'avatar' => $profile->avatar,
            'avatar_url' => $profileData['avatar_url'],
            'canEdit' => $canEdit,
        ]);

        return Inertia::render('Worker/Profile/Show', [
            'profile' => $profileData,
            'reviews' => $reviews,
            'stats' => $stats,
            'canEdit' => $canEdit,
        ]);
    }

    public function edit()
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Необхідно авторизуватися');
        }

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        Gate::authorize('update', $profile);

        $profileData = $profile->toArray();

        // ✅ ЯК В ORDER CONTROLLER - ВИКОРИСТОВУЄМО temporaryUrl
        $profileData['avatar_url'] = $profile->avatar
            ? Storage::disk('s3')->temporaryUrl($profile->avatar, now()->addMinutes(60))
            : null;

        $profileData['avatar'] = $profile->avatar;

        return Inertia::render('Worker/Profile/Edit', [
            'profile' => $profileData,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Необхідно авторизуватися');
        }

        $profile = $user->workerProfile ?? new WorkerProfile(['user_id' => $user->id]);

        Gate::authorize('update', $profile);

        $validated = $request->validate([
            'bio' => 'nullable|string|max:5000',
            'skills' => 'nullable|string|max:1000',
            'experience' => 'nullable|string|max:5000',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // ✅ ЯК В ORDER CONTROLLER - ОБРОБКА ФАЙЛІВ
        if ($request->hasFile('avatar')) {
            \Log::info('Processing avatar...');

            // Видаляємо старий аватар
            if ($profile->avatar) {
                Storage::disk('s3')->delete($profile->avatar);
                \Log::info('Old avatar deleted: ' . $profile->avatar);
            }

            // Зберігаємо новий
            $path = $request->file('avatar')->store('avatars', 's3');
            $validated['avatar'] = $path;
            \Log::info('Avatar saved to S3: ' . $path);
        }

        $profile->fill($validated);
        $profile->save();

        return redirect()->route('worker.profile.edit')
            ->with('success', 'Профіль успішно оновлено');
    }

    public function destroy(WorkerProfile $profile)
    {
        Gate::authorize('delete', $profile);

        if ($profile->avatar) {
            Storage::disk('s3')->delete($profile->avatar);
        }

        $profile->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Профіль успішно видалено');
    }

    /**
     * ✅ Вспомогательный метод для декодирования JSON полей (как в OrderController)
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
