<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksResourcePermissions;
use Illuminate\Database\Eloquent\Model;

class UserPolicy
{
    use ChecksResourcePermissions {
        delete as protected hasDeletePermission;
    }

    protected function permissionPrefix(): string
    {
        return 'users';
    }

    public function delete(User $user, Model $model): bool
    {
        // Prevent authenticated administrators from accidentally deleting their own account.
        if ($user->is($model)) {
            return false;
        }

        return $this->hasDeletePermission($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->hasDeletePermission($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $this->hasDeletePermission($user, $model);
    }
}
