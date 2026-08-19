<?php

namespace Tests\Feature\Client;

use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_order_with_tag()
    {
        // Создаём роль
        Role::forceCreate([
            'id' => 3,
            'name' => 'client',
            'display_name' => 'Клієнт'
        ]);

        // Даём клиенту денег, чтобы он мог создать заявку
        $client = User::factory()->create(['role_id' => 3, 'balance' => 1000]);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($client)->post(route('client.orders.store'), [
            'title' => 'Test Order',
            'description' => 'Need help with code',
            'price' => 100,
            'tag_id' => $tag->id,
        ]);

        $response->assertRedirect(route('client.orders.index'));
        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'title' => 'Test Order',
        ]);
    }

    public function test_client_can_visit_order_create_page()
    {
        // 1. Создаём роль клиента (мы уже знаем, как это делается)
        Role::forceCreate([
            'id' => 3,
            'name' => 'client',
            'display_name' => 'Клієнт'
        ]);

        // 2. Создаём клиента
        $client = User::factory()->create(['role_id' => 3]);

        // 3. Логиним клиента
        $response = $this->actingAs($client)->get(route('client.orders.create'));

        // 4. Проверяем, что страница открылась (код 200)
        $response->assertStatus(200);
    }

    public function test_client_cannot_edit_other_clients_order()
    {
        // Создаём роль
        Role::forceCreate([
            'id' => 3,
            'name' => 'client',
            'display_name' => 'Клієнт'
        ]);

        // Создаём двух клиентов
        $client1 = User::factory()->create(['role_id' => 3]);
        $client2 = User::factory()->create(['role_id' => 3]);

        // Клиент 1 создаёт заявку
        $order = Order::factory()->create(['client_id' => $client1->id]);

        // Клиент 2 пытается зайти на страницу редактирования чужой заявки
        $response = $this->actingAs($client2)->get(route('client.orders.edit', $order));

        // Проверяем, что доступ запрещён (403 Forbidden)
        $response->assertStatus(403);
    }

    public function test_client_can_edit_myself_client_order()
    {
        // Создаём роль
        Role::forceCreate([
            'id' => 3,
            'name' => 'client',
            'display_name' => 'Клієнт'
        ]);

        // Создаём двух клиентов
        $client = User::factory()->create(['role_id' => 3]);

        // Клиент 1 создаёт заявку
        $order = Order::factory()->create(['client_id' => $client->id]);

        // Клиент 2 пытается зайти на страницу редактирования чужой заявки
        $response = $this->actingAs($client)->put(route('client.orders.update', $order), ['title' => 'New Updated Title'])->assertRedirect(route('client.orders.index'));


        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'title' => 'New Updated Title'
        ]);
    }

    public function test_client_redirects_to_dashboard_if_insufficient_balance()
    {
        // 1. Створюємо роль
        Role::forceCreate([
            'id' => 3,
            'name' => 'client',
            'display_name' => 'Клієнт'
        ]);

        // 2. Створюємо клієнта з маленьким балансом (50 грн)
        $client = User::factory()->create([
            'role_id' => 3,
            'balance' => 50
        ]);

        // 3. Створюємо тег для заявки
        $tag = Tag::factory()->create();

        // 4. Клієнт намагається створити заявку за 100 грн (балансу не вистачає)
        $response = $this->actingAs($client)->post(route('client.orders.store'), [
            'title'       => 'Test Order',
            'description' => 'Need help with code',
            'price'       => 100,
            'tag_id'      => $tag->id,
        ]);

        // 5. Перевіряємо, що сталося перенаправлення на дашборд
        $response->assertRedirect(route('client.dashboard'));

        // 6. Перевіряємо, що у сесії з'явилася помилка з правильним текстом
        $response->assertSessionHas('error', 'Поповніть баланс перед створенням заявки');

        // 7. Перевіряємо, що заявка НЕ була створена в базі даних
        $this->assertDatabaseMissing('orders', [
            'client_id' => $client->id,
            'title'     => 'Test Order'
        ]);
    }
}
