<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminReviewController extends Controller
{
    /**
     * Список всех отзывов (для админа)
     */
    public function index(Request $request)
    {
        $query = Review::with(['client', 'worker', 'order']);

        // Поиск по комментарию или имени
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('worker', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Фильтр по рейтингу
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->latest()->paginate(10)->withQueryString();

        // Статистика
        $stats = [
            'total' => Review::count(),
            'average' => round(Review::avg('rating') ?? 0, 1),
            'ratings' => [
                1 => Review::where('rating', 1)->count(),
                2 => Review::where('rating', 2)->count(),
                3 => Review::where('rating', 3)->count(),
                4 => Review::where('rating', 4)->count(),
                5 => Review::where('rating', 5)->count(),
            ],
        ];

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'filters' => $request->only(['search', 'rating']),
        ]);
    }

    /**
     * Удалить отзыв (только для админа)
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Відгук успішно видалено');
    }
}
