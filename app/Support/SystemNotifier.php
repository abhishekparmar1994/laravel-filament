<?php

namespace App\Support;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;

class SystemNotifier
{
    /**
     * Send a persistent database notification immediately (even when QUEUE_CONNECTION=database).
     */
    public static function notifyUser(User $user, string $title, string $body, string $status = 'info'): void
    {
        try {
            $databaseNotification = Notification::make()
                ->title($title)
                ->body($body)
                ->status($status)
                ->toDatabase();

            $user->notifyNow($databaseNotification);
        } catch (QueryException) {
            // Skip cleanly if the notifications table has not been migrated yet.
        }
    }

    /**
     * Send a persistent database notification immediately to all active Super Admins via Eloquent relationships.
     */
    public static function notifyAdmins(string $title, string $body, string $status = 'info', ?User $except = null): void
    {
        try {
            $admins = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
                ->when($except instanceof User, fn ($query) => $query->whereKeyNot($except->getKey()))
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            $databaseNotification = Notification::make()
                ->title($title)
                ->body($body)
                ->status($status)
                ->toDatabase();

            foreach ($admins as $admin) {
                $admin->notifyNow($databaseNotification);
            }
        } catch (QueryException) {
            // Skip cleanly if the notifications table has not been migrated yet.
        }
    }
}
