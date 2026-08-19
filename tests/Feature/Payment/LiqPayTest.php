<?php

namespace Tests\Feature\Payment;

use App\Models\Role;
use App\Models\User;
use App\Models\Transaction;
use App\Services\LiqpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiqPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_creates_pending_transaction()
    {
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        $user = User::factory()->create(['role_id' => 3, 'balance' => 0]);

        Http::fake([
            '*' => Http::response(['redirect_url' => 'https://liqpay.ua/checkout/...'], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('payment.deposit'), [
            'amount' => 100,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['redirect_url']);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 100,
            'status' => 'pending',
        ]);
    }

    public function test_callback_completes_transaction_and_updates_balance()
    {
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        $user = User::factory()->create(['role_id' => 3, 'balance' => 0]);

        // Створюємо транзакцію
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 100,
            'balance_after' => 0,
            'status' => 'pending',
            'payment_id' => 'test_order_123',
        ]);

        // 🟢 ДОДАЄМО МОК ДЛЯ LiqpayService, щоб verifySignature завжди повертав true
        $this->mock(LiqpayService::class, function ($mock) {
            $mock->shouldReceive('verifySignature')
                ->once()
                ->andReturn(true);
        });

        // Емулюємо запит від LiqPay
        $response = $this->postJson(route('liqpay.callback'), [
            'order_id' => 'test_order_123',
            'status' => 'success',
        ]);

        $response->assertOk();

        // Перевіряємо баланс
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => 100,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);
    }
}
