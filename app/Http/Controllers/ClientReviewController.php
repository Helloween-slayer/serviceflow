<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientReviewController extends Controller
{
    /**
     * Список отзывов, которые оставил текущий клиент
     */
    public function index()
    {
        $user = auth()->user();

        // Получаем отзывы, где клиент = текущий пользователь
        $reviews = Review::with(['worker', 'order'])
            ->where('client_id', $user->id)
            ->latest()
            ->paginate(10);

        // Количество отзывов
        $totalReviews = $reviews->total();

        return Inertia::render('Client/Reviews/Index', [
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
        ]);
    }
}
