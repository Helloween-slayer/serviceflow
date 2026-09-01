<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message; //  Передаем сообщение

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // Канал, куда отправляем событие (для конкретной заявки)
    public function broadcastOn(): Channel
    {
        return new Channel('order.' . $this->message->order_id);
    }

    // Данные, которые отправляются в вебсокет
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'user_name' => $this->message->user->name,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at->diffForHumans(),
        ];
    }
}
