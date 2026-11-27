<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class VersionedCache
{
    public static function version(string $namespace): int
    {
        return (int) Cache::rememberForever("{$namespace}:version", fn (): int => 1);
    }

    public static function bump(string $namespace): void
    {
        $next = static::version($namespace) + 1;
        Cache::forever("{$namespace}:version", $next);
    }

    public static function remember(string $namespace, string $key, int $ttlSeconds, Closure $resolver): mixed
    {
        $version = static::version($namespace);

        return Cache::remember(
            "{$namespace}:v{$version}:{$key}",
            now()->addSeconds($ttlSeconds),
            $resolver,
        );
    }

    public static function storeScopeId(): int
    {
        return spl_object_id(Cache::store());
    }
}
