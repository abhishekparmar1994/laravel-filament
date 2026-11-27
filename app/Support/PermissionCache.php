<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

class PermissionCache
{
    private const NAMESPACE = 'rbac';

    private const TTL_SECONDS = 600;

    /**
     * Per-process memoization keyed by cache-store instance, version, and user ID.
     *
     * @var array<string, array{roles: array<int, string>, permissions: array<int, string>}>
     */
    private static array $memory = [];

    /**
     * @return array{roles: array<int, string>, permissions: array<int, string>}
     */
    public static function forUser(User $user): array
    {
        $version = VersionedCache::version(self::NAMESPACE);
        $memoryKey = VersionedCache::storeScopeId().":{$version}:{$user->getKey()}";

        if (isset(self::$memory[$memoryKey])) {
            return self::$memory[$memoryKey];
        }

        $resolved = VersionedCache::remember(
            self::NAMESPACE,
            "user:{$user->getKey()}",
            self::TTL_SECONDS,
            function () use ($user): array {
                $roles = $user->roles()
                    ->with('permissions:id,slug')
                    ->get(['roles.id', 'roles.slug']);

                return [
                    'roles' => $roles->pluck('slug')->values()->all(),
                    'permissions' => $roles
                        ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
                        ->unique()
                        ->values()
                        ->all(),
                ];
            },
        );

        return self::$memory[$memoryKey] = $resolved;
    }

    public static function flush(): void
    {
        self::$memory = [];
        VersionedCache::bump(self::NAMESPACE);
    }
}
