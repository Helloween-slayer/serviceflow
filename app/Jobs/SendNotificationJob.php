<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\OrderStatusChangedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Order $order;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(Order $order, string $oldStatus, string $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function handle(): void
    {
        $order = $this->order;
        $oldStatus = $this->oldStatus;
        $newStatus = $this->newStatus;

        $statusMap = [
            'new' => '🆕 Нова',
            'in_progress' => '🔄 В роботі',
            'completed' => '✅ Завершена',
            'cancelled' => '❌ Скасована',
        ];

        // ========== 1. TELEGRAM ==========
        $message = "📋 <b>Статус заявки змінено</b>\n\n" .
            "📌 <b>{$order->title}</b>\n" .
            "🔄 {$statusMap[$oldStatus]} → {$statusMap[$newStatus]}\n" .
            "💰 {$order->price} ₴\n\n" .
            "🔗 <a href='" . route('orders.show', $order->id) . "'>Переглянути заявку</a>";

        $token = env('TELEGRAM_BOT_TOKEN');

        if ($token) {
            $chatIds = [];

            if ($order->client->telegram_id && $order->client->telegram_notifications) {
                $chatIds[] = $order->client->telegram_id;
            }

            if ($order->worker && $order->worker->telegram_id && $order->worker->telegram_notifications) {
                $chatIds[] = $order->worker->telegram_id;
            }

            foreach ($chatIds as $chatId) {
                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
            }
        }

        // Отправляем клиенту через 1 секунду
        Mail::to($order->client->email)
            ->later(now()->addSeconds(1), new OrderStatusChangedMail($order, $oldStatus, $newStatus));

        if ($order->worker) {
            Mail::to($order->worker->email)
                ->later(now()->addSeconds(2), new OrderStatusChangedMail($order, $oldStatus, $newStatus));
        }
    }
}
