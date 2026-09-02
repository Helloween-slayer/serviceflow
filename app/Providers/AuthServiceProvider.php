<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Policies\OrderPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkerProfilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        User::class => UserPolicy::class,
        Tag::class => TagPolicy::class,
        WorkerProfile::class => WorkerProfilePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
