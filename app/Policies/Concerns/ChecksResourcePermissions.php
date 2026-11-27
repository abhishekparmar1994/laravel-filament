<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksResourcePermissions
{
    abstract protected function permissionPrefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission("{$this->permissionPrefix()}.view_any");
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission("{$this->permissionPrefix()}.view");
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("{$this->permissionPrefix()}.create");
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission("{$this->permissionPrefix()}.update");
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission("{$this->permissionPrefix()}.delete");
    }
}
