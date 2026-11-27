<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\DashboardCache;
use App\Support\PermissionCache;
use App\Support\SystemNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        PermissionCache::flush();
        DashboardCache::flush();

        if ($this->record instanceof User) {
            SystemNotifier::notifyUser(
                $this->record,
                'Welcome to '.config('app.name'),
                'Your admin panel account has been created.',
                'success',
            );

            $actor = auth()->user();

            SystemNotifier::notifyAdmins(
                'New User Created',
                "{$this->record->name} ({$this->record->email}) was added.",
                'info',
                $actor instanceof User ? $actor : null,
            );
        }
    }
}
