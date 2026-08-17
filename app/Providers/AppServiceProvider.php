<?php

namespace App\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->make(\Illuminate\Foundation\Vite::class)->prefetch(concurrency: 3);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
