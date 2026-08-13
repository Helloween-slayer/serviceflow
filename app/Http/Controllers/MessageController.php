<?php

namespace App\Http\Controllers;

use App\Events\NewMessageEvent;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function index(Order $order)
    {
        // Проверяем, может ли пользователь видеть этот чат
        Gate::authorize('view', $order);

        $messages = Message::where('order_id', $order->id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json($messages);
    }

    public function store(Request $request, Order $order)
    {
        \Log::info('=== MESSAGE STORE ===');
        \Log::info('Order ID: ' . $order->id);
        \Log::info('User ID: ' . auth()->id());
        \Log::info('Message: ' . $request->message);

        try {
            // Проверяем, может ли пользователь писать в этот чат
            Gate::authorize('view', $order);

            $validated = $request->validate([
                'message' => 'required|string|max:1000',
            ]);

            $message = Message::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
            ]);

            $message->load('user');

            NewMessageEvent::dispatch($message);

            \Log::info('Message created: ' . $message->id);

            return response()->json($message);
        } catch (\Exception $e) {
            \Log::error('Message store error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
