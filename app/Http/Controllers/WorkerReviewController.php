<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkerReviewController extends Controller
{
    /**
     * Список отзывов для текущего воркера
     */
    public function index()
    {
        $user = auth()->user();

        // Получаем отзывы, где воркер = текущий пользователь
        $reviews = Review::with(['client', 'order'])
            ->where('worker_id', $user->id)
            ->latest()
            ->paginate(10);

        // Средний рейтинг
        $averageRating = Review::where('worker_id', $user->id)->avg('rating') ?? 0;

        return Inertia::render('Worker/Reviews/Index', [
            'reviews' => $reviews,
            'averageRating' => round($averageRating, 1),
            'totalReviews' => $reviews->total(),
        ]);
    }
}
