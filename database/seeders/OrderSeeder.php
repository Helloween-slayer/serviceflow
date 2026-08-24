<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Если пользователь с ролью клиента уже есть — берём его
        $client = User::where('role_id', 3)->first();

        if (!$client) {
            $client = User::factory()->create([
                'name' => 'Тестовий клієнт',
                'email' => 'client@test.com',
                'password' => bcrypt('password'),
                'role_id' => 3,
            ]);
        }

        // Получаем теги
        $tags = Tag::all();

        // Если заявок нет — создаём 10
        if (Order::count() < 10) {
            $orders = Order::factory()->count(10)->create([
                'client_id' => $client->id,
                'status' => 'new',
                'worker_id' => null,
            ]);

            foreach ($orders as $order) {
                $randomTags = $tags->random(rand(1, 3))->pluck('id')->toArray();
                $order->tags()->attach($randomTags);
            }
        }
    }
}
