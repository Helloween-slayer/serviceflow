<?php

namespace Tests\Feature\Client;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_create_review_for_incomplete_order()
    {
        // 1. Створюємо ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт, воркер та заявка в роботі (in_progress)
        $client = User::factory()->create(['role_id' => 3]);
        $worker = User::factory()->create(['role_id' => 2]);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'worker_id' => $worker->id,
            'status' => 'in_progress', // <--- Не завершена!
        ]);

        // 3. Клієнт намагається відкрити сторінку створення відгуку
        $response = $this->actingAs($client)->get(route('client.reviews.create', ['order' => $order]));

        // 4. Має бути 403 Forbidden (доступ заборонено)
        $response->assertForbidden();
    }

    public function test_client_can_create_review_for_completed_order()
    {
        // 1. Ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт, воркер
        $client = User::factory()->create(['role_id' => 3]);
        $worker = User::factory()->create(['role_id' => 2]);

        // 2.1 Створюємо профіль воркера, щоб тест міг перевірити рейтинг
        WorkerProfile::create([
            'user_id' => $worker->id,
        ]);

        // 3. Заявка завершена (completed)
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'worker_id' => $worker->id,
            'status' => 'completed',
        ]);

        // 4. Клієнт створює відгук
        $response = $this->actingAs($client)->post(route('client.reviews.store'), [
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Чудова робота!',
        ]);

        // 5. Перевірки
        $response->assertRedirect(route('orders.show', $order->id));

        // Відгук з'явився в базі
        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'client_id' => $client->id,
            'worker_id' => $worker->id,
            'rating' => 5,
            'comment' => 'Чудова робота!',
        ]);

        // Рейтинг воркера оновився
        $this->assertDatabaseHas('worker_profiles', [
            'user_id' => $worker->id,
            'rating' => 5.0,
        ]);
    }
}
