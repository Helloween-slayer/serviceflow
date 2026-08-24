<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём роли автоматически (firstOrCreate - не дублирует)
        Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Адміністратор']);
        Role::firstOrCreate(['name' => 'worker'], ['display_name' => 'Виконавець']);
        Role::firstOrCreate(['name' => 'client'], ['display_name' => 'Клієнт']);

        // Создаём теги автоматически
        Tag::firstOrCreate(['name' => 'Програмування']);
        Tag::firstOrCreate(['name' => 'Дизайн']);
        Tag::firstOrCreate(['name' => 'Копірайтинг']);
    }
}
