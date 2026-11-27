<?php

namespace App\Support;

use Closure;

class DashboardCache
{
    private const NAMESPACE = 'dashboard';

    private const TTL_SECONDS = 300;

    public static function remember(string $key, Closure $resolver): mixed
    {
        return VersionedCache::remember(self::NAMESPACE, $key, self::TTL_SECONDS, $resolver);
    }

    public static function flush(): void
    {
        VersionedCache::bump(self::NAMESPACE);
    }
}
