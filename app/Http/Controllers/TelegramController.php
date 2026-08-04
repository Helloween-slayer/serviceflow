<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    /**
     * Привязка Telegram к аккаунту
     */
    public function connect(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'telegram_id' => 'required|string|unique:users,telegram_id',
        ]);

        $user->update([
            'telegram_id' => $request->telegram_id,
            'telegram_notifications' => true,
        ]);

        // Отправляем приветственное сообщение
        $this->sendTelegramMessage($request->telegram_id,
            "✅ Ви успішно підключили Telegram до ServiceFlow!\n\n" .
            "Тепер ви будете отримувати сповіщення про зміну статусу ваших заявок."
        );

        return redirect()->back()->with('success', 'Telegram успішно підключено!');
    }

    /**
     * Отвязка Telegram
     */
    public function disconnect(Request $request)
    {
        $user = auth()->user();
        $telegramId = $user->telegram_id;

        // Отправляем сообщение об отвязке
        if ($telegramId) {
            $this->sendTelegramMessage($telegramId,
                "❌ Ви відключили Telegram від ServiceFlow.\n" .
                "Ви більше не будете отримувати сповіщення."
            );
        }

        $user->update([
            'telegram_id' => null,
            'telegram_notifications' => false,
        ]);

        return redirect()->back()->with('success', 'Telegram відключено');
    }

    /**
     * Переключение уведомлений
     */
    public function toggleNotifications(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'telegram_notifications' => 'required|boolean',
        ]);

        $user->update([
            'telegram_notifications' => $request->telegram_notifications,
        ]);

        return redirect()->back()->with('success', 'Налаштування сповіщень оновлено');
    }

    /**
     * Webhook для Telegram бота
     * Обрабатывает команды от пользователей
     */

    /**  public function webhook(Request $request)
    {
        $data = $request->all();

        // Проверяем, есть ли сообщение
        if (!isset($data['message'])) {
            return response()->json(['status' => 'ok']);
        }

        $chatId = $data['message']['chat']['id'];
        $text = $data['message']['text'] ?? '';

        // Обработка команды /start
        if ($text === '/start' || str_starts_with($text, '/start')) {
            $this->sendTelegramMessage(
                $chatId,
                "👋 Вітаємо в <b>ServiceFlow</b>!\n\n" .
                "📌 Ваш Telegram ID:\n" .
                "<code>{$chatId}</code>\n\n" .
                "🔑 Скопіюйте цей ID та вставте його на сайті, щоб підключити сповіщення.\n\n" .
                "🔗 <a href='" . route('dashboard') . "'>Перейти до сайту</a>"
            );
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Отправка сообщения в Telegram
     */
    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        if (!$token) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }
}
