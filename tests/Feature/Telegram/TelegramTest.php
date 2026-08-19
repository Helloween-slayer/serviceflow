<?php

namespace Tests\Feature\Telegram;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_connect_telegram()
    {
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        $user = User::factory()->create(['role_id' => 3]);

        $response = $this->actingAs($user)->post(route('telegram.connect'), [
            'telegram_id' => '123456789',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_id' => '123456789',
            'telegram_notifications' => true,
        ]);
    }

    public function test_user_can_disconnect_telegram()
    {
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        $user = User::factory()->create([
            'role_id' => 3,
            'telegram_id' => '123456789',
            'telegram_notifications' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('telegram.disconnect'));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_id' => null,
            'telegram_notifications' => false,
        ]);
    }
}
