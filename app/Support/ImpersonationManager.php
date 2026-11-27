<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ImpersonationManager
{
    public const SESSION_KEY = 'impersonating_from_id';

    public static function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public static function impersonatorId(): ?int
    {
        $id = session()->get(self::SESSION_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    public static function canImpersonate(?User $impersonator, User $target): bool
    {
        if (! $impersonator instanceof User) {
            return false;
        }

        if ($impersonator->is($target) || ! $target->is_active) {
            return false;
        }

        return $impersonator->hasPermission('users.impersonate');
    }

    public static function start(User $impersonator, User $target): void
    {
        if (! static::canImpersonate($impersonator, $target)) {
            throw new InvalidArgumentException('You are not authorized to impersonate this user.');
        }

        // Preserve the original admin ID across session regeneration when swapping users.
        $originId = static::impersonatorId() ?? $impersonator->id;

        static::recordEvent($impersonator->id, $target, 'impersonated', "Started impersonating {$target->email}");

        Auth::login($target);
        session()->put(self::SESSION_KEY, $originId);
    }

    public static function stop(): bool
    {
        $impersonatorId = static::impersonatorId();

        if ($impersonatorId === null) {
            return false;
        }

        $impersonator = User::query()->find($impersonatorId);
        $currentTarget = Auth::user();

        session()->forget(self::SESSION_KEY);

        if ($impersonator instanceof User) {
            if ($currentTarget instanceof User) {
                static::recordEvent($impersonator->id, $currentTarget, 'stopped_impersonation', "Stopped impersonating {$currentTarget->email}");
            }

            Auth::login($impersonator);

            return true;
        }

        Auth::logout();

        return false;
    }

    protected static function recordEvent(int $actorId, User $target, string $event, string $description): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => $actorId,
            'event' => $event,
            'subject_type' => User::class,
            'subject_id' => $target->getKey(),
            'description' => $description,
            'ip_address' => request()?->ip(),
        ]);
    }
}
