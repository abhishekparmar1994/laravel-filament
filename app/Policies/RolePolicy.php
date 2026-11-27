<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\ChecksResourcePermissions;
use Illuminate\Database\Eloquent\Model;

class RolePolicy
{
    use ChecksResourcePermissions {
        delete as protected hasDeletePermission;
    }

    protected function permissionPrefix(): string
    {
        return 'roles';
    }

    public function delete(User $user, Model $model): bool
    {
        // Keep the built-in super-admin role protected from accidental deletion.
        if ($model instanceof Role && $model->slug === 'super-admin') {
            return false;
        }

        return $this->hasDeletePermission($user, $model);
    }
}
