<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Дизайн', 'slug' => 'dizayn'],
            ['name' => 'Програмування', 'slug' => 'programuvannya'],
            ['name' => 'Копірайтинг', 'slug' => 'kopiraiting'],
            ['name' => 'Верстка', 'slug' => 'verstka'],
            ['name' => 'Консультація', 'slug' => 'konsultatsiya'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['name' => $tag['name']],
                ['slug' => $tag['slug']]
            );
        }
    }
}
