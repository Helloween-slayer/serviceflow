<?php

namespace Tests\Feature\Worker;

use App\Models\Order;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_can_take_order_and_money_is_held()
    {
        // 1. Создаём роли
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Создаём клиента (с деньгами) и воркера
        $client = User::factory()->create(['role_id' => 3, 'balance' => 500]);
        $worker = User::factory()->create(['role_id' => 2, 'balance' => 0]);

        // 3. Создаём заявку (новая, без воркера, цена 100)
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => 'new',
            'worker_id' => null,
            'price' => 100,
        ]);

        // 4. Воркер пытается взять заявку
        $response = $this->actingAs($worker)->put(route('worker.orders.take', $order));

        // 5. Проверки
        $response->assertRedirect(route('worker.orders.index'));

        // Статус заявки стал in_progress, worker_id привязался
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
            'worker_id' => $worker->id,
        ]);

        // Деньги списались с клиента (500 -> 400)
        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'balance' => 400,
        ]);

        // Создалась транзакция холда
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'type' => 'hold',
            'status' => 'completed',
            'amount' => -100,
        ]);
    }

    public function test_worker_cannot_take_order_if_client_has_no_balance()
    {
        // 1. Создаём роли
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клиент с недостаточным балансом (50 грн, а цена заявки 100)
        $client = User::factory()->create(['role_id' => 3, 'balance' => 50]);
        $worker = User::factory()->create(['role_id' => 2, 'balance' => 0]);

        // 3. Создаём заявку
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => 'new',
            'worker_id' => null,
            'price' => 100,
        ]);

        // 4. Воркер пытается взять заявку
        $response = $this->actingAs($worker)->put(route('worker.orders.take', $order));

        // 5. Проверки
        // Должен быть редирект назад (на ту же страницу)
        $response->assertRedirect();

        // В сессии должна быть ошибка
        $response->assertSessionHas('error', 'У клієнта недостатньо коштів для оплати заявки.');

        // Заявка НЕ должна измениться (статус new, worker_id null)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'new',
            'worker_id' => null,
        ]);

        // Деньги НЕ должны списаться (баланс клиента остался 50)
        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'balance' => 50,
        ]);

        // Транзакция НЕ должна создаться
        $this->assertDatabaseMissing('transactions', [
            'order_id' => $order->id,
        ]);
    }

    public function test_worker_cannot_take_already_taken_order()
    {
        // 1. Ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт, Воркер_1 (який уже взяв), Воркер_2 (який пробує взяти)
        $client = User::factory()->create(['role_id' => 3, 'balance' => 500]);
        $worker1 = User::factory()->create(['role_id' => 2]);
        $worker2 = User::factory()->create(['role_id' => 2]);

        // 3. Заявка, яку вже взяв worker1
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => 'in_progress',
            'worker_id' => $worker1->id,
            'price' => 100,
        ]);

        // 4. Воркер_2 пробує взяти
        $response = $this->actingAs($worker2)->put(route('worker.orders.take', $order));

        // 5. Перевірки
        // Має бути 403 Forbidden (доступ заборонено)
        $response->assertForbidden();

        // Заявка має залишитися в тому ж стані (worker_id = worker1)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
            'worker_id' => $worker1->id,
        ]);
    }

    public function test_worker_can_complete_order_and_gets_paid()
    {
        // 1. Ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт з грошима, воркер з нульовим балансом
        $client = User::factory()->create(['role_id' => 3, 'balance' => 500]);
        $worker = User::factory()->create(['role_id' => 2, 'balance' => 0]);

        // 3. Заявка вже в роботі (in_progress), належить цьому воркеру
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'worker_id' => $worker->id,
            'status' => 'in_progress',
            'price' => 100,
        ]);

        // 4. Воркер завершує заявку
        $response = $this->actingAs($worker)->put(route('worker.orders.complete', $order));

        // 5. Перевірки
        $response->assertRedirect(route('worker.orders.index'));

        // Статус заявки став completed
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);

        // Воркер отримав гроші (було 0, стало 100)
        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'balance' => 100,
        ]);

        // У клієнта гроші залишилися без змін (заявка була в роботі, гроші вже списані)
        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'balance' => 500, // або 400, якщо в тесті №1 ти залишив списання — але тут ми просто перевіряємо
        ]);
    }

    public function test_worker_cannot_complete_other_workers_order()
    {
        // 1. Ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт, Воркер1, Воркер2
        $client = User::factory()->create(['role_id' => 3, 'balance' => 500]);
        $worker1 = User::factory()->create(['role_id' => 2]);
        $worker2 = User::factory()->create(['role_id' => 2]);

        // 3. Заявка в роботі у Воркера1
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'worker_id' => $worker1->id,
            'status' => 'in_progress',
            'price' => 100,
        ]);

        // 4. Воркер2 намагається завершити її
        $response = $this->actingAs($worker2)->put(route('worker.orders.complete', $order));

        // 5. Перевірки
        // Має бути 403 Forbidden
        $response->assertForbidden();

        // Заявка має залишитися в тому ж стані (in_progress, worker_id = worker1)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
            'worker_id' => $worker1->id,
        ]);
    }

    public function test_worker_can_cancel_order_and_client_gets_refunded()
    {
        // 1. Ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт з грошима та воркер
        $client = User::factory()->create(['role_id' => 3, 'balance' => 500]);
        $worker = User::factory()->create(['role_id' => 2, 'balance' => 0]);

        // 3. Заявка в роботі у воркера
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'worker_id' => $worker->id,
            'status' => 'in_progress',
            'price' => 100,
        ]);

        // 4. Створюємо транзакцію холда
        \App\Models\Transaction::create([
            'order_id' => $order->id,
            'type' => 'hold',
            'status' => 'completed',
            'amount' => -100,
            'user_id' => $client->id,
            'balance_after' => 400,
        ]);

        // 5. Воркер скасовує заявку
        $response = $this->actingAs($worker)->put(route('worker.orders.cancel', $order));

        // 6. Перевірки
        $response->assertRedirect(route('worker.orders.index'));

        // Статус заявки став new (так працює CancelOrderAction)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'new',
        ]);

        // Клієнт отримав гроші назад (було 500, стало 600)
        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'balance' => 600,
        ]);
    }

    public function test_worker_cannot_cancel_other_workers_order()
    {
        // 1. Ролі
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        Role::forceCreate(['id' => 2, 'name' => 'worker', 'display_name' => 'Воркер']);

        // 2. Клієнт, Воркер1, Воркер2
        $client = User::factory()->create(['role_id' => 3, 'balance' => 500]);
        $worker1 = User::factory()->create(['role_id' => 2]);
        $worker2 = User::factory()->create(['role_id' => 2]);

        // 3. Заявка в роботі у Воркера1
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'worker_id' => $worker1->id,
            'status' => 'in_progress',
            'price' => 100,
        ]);

        // 4. Воркер2 намагається скасувати
        $response = $this->actingAs($worker2)->put(route('worker.orders.cancel', $order));

        // 5. Перевірки
        // Має бути 403 Forbidden
        $response->assertForbidden();

        // Заявка має залишитися в тому ж стані
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'in_progress',
            'worker_id' => $worker1->id,
        ]);
    }
}
