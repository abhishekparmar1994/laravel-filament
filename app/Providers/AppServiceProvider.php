<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Allow direct Gate/Blade @can('permission.slug') checks while letting model policies enforce safety guards.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($arguments === [] && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
