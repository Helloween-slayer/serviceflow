<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    // Тест Адмін може зайти на дашборд
    public function test_admin_can_access_admin_dashboard()
    {
        Role::forceCreate(['id' => 1, 'name' => 'admin', 'display_name' => 'Адмін']);
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    // Тест Клієнт НЕ може зайти в адмінку
    public function test_client_cannot_access_admin_dashboard()
    {
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);
        $client = User::factory()->create(['role_id' => 3]);

        $response = $this->actingAs($client)->get(route('admin.dashboard'));
        $response->assertForbidden(); // 403
    }

    // Тест Адмін НЕ може видалити самого себе
    public function test_admin_cannot_delete_self()
    {
        Role::forceCreate(['id' => 1, 'name' => 'admin', 'display_name' => 'Адмін']);
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        // Gate спрацював раніше → 403 Forbidden
        $response->assertForbidden();

        // Користувач має залишитися в базі
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    // Тест Адмін НЕ може видалити останнього адміна
    public function test_admin_cannot_delete_last_admin()
    {
        Role::forceCreate(['id' => 1, 'name' => 'admin', 'display_name' => 'Адмін']);
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        // Gate спрацював раніше → 403 Forbidden
        $response->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    // Тест Адмін створює новий тег
    public function test_admin_can_create_tag()
    {
        Role::forceCreate(['id' => 1, 'name' => 'admin', 'display_name' => 'Адмін']);
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Новий тег',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['name' => 'Новий тег']);
    }

    // Тест Адмін НЕ може видалити тег, який використовується
    public function test_admin_cannot_delete_used_tag()
    {
        Role::forceCreate(['id' => 1, 'name' => 'admin', 'display_name' => 'Адмін']);

        // ДОДАЙ ЦЮ СТРОЧКУ (роль клієнта):
        Role::forceCreate(['id' => 3, 'name' => 'client', 'display_name' => 'Клієнт']);

        $admin = User::factory()->create(['role_id' => 1]);
        $client = User::factory()->create(['role_id' => 3]); // тепер це спрацює!

        $tag = Tag::factory()->create(['name' => 'Used Tag']);
        $order = Order::factory()->create([
            'client_id' => $client->id,
        ]);

        $order->tags()->attach($tag->id);

        $response = $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Неможливо видалити тег, який використовується в заявках');

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    // Адмін переглядає всі заявки
    public function test_admin_can_view_all_orders()
    {
        Role::forceCreate(['id' => 1, 'name' => 'admin', 'display_name' => 'Адмін']);
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));
        $response->assertStatus(200);
    }
}
