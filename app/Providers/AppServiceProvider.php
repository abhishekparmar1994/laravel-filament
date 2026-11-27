<?php

namespace App\Providers;

use App\Models\User;
use App\Support\ImpersonationManager;
use App\Support\SystemNotifier;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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

        Event::listen(Login::class, function (Login $event): void {
            if (! ($event->user instanceof User)) {
                return;
            }

            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

            if (! ImpersonationManager::isImpersonating()) {
                $ip = request()?->ip() ?? 'unknown IP';

                SystemNotifier::notifyUser(
                    $event->user,
                    'New Sign-In Recorded',
                    "Signed in from {$ip}.",
                    'info',
                );
            }
        });
    }
}
