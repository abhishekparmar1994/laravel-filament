<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('activity_logs.view_any');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasPermission('activity_logs.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
