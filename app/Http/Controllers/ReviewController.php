<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{


    /**
     * Форма создания отзыва
     */
    public function create(Order $order)
    {
        // Проверяем: заявка завершена и клиент — текущий пользователь
        if ($order->status !== 'completed' || $order->client_id !== auth()->id()) {
            abort(403, 'Ви не можете залишити відгук для цієї заявки');
        }

        // Проверяем: отзыв уже есть?
        if (Review::where('order_id', $order->id)->exists()) {
            return redirect()->route('orders.show', $order->id)
                ->with('info', 'Ви вже залишили відгук для цієї заявки');
        }

        return Inertia::render('Client/Reviews/Create', [
            'order' => $order,
        ]);
    }

    /**
     * Сохранить отзыв
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:5000',
        ]);

        $order = Order::find($validated['order_id']);

        // Проверяем права
        if ($order->status !== 'completed' || $order->client_id !== auth()->id()) {
            abort(403, 'Ви не можете залишити відгук для цієї заявки');
        }

        // Создаем отзыв
        Review::create([
            'order_id' => $order->id,
            'client_id' => auth()->id(),
            'worker_id' => $order->worker_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        // Обновляем рейтинг воркера
        $this->updateWorkerRating($order->worker_id);

        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Дякуємо за ваш відгук!');
    }

    /**
     * Обновить рейтинг воркера
     */
    private function updateWorkerRating($workerId)
    {
        $average = Review::where('worker_id', $workerId)->avg('rating');
        $count = Review::where('worker_id', $workerId)->count();

        $profile = WorkerProfile::where('user_id', $workerId)->first();
        if ($profile) {
            $profile->update([
                'rating' => round($average, 2),
                'completed_orders' => $count,
            ]);
        }
    }
}
