<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksResourcePermissions;

class PermissionPolicy
{
    use ChecksResourcePermissions;

    protected function permissionPrefix(): string
    {
        return 'permissions';
    }
}
