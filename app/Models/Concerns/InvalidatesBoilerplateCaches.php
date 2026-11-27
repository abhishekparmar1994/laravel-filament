<?php

namespace App\Models\Concerns;

use App\Support\DashboardCache;
use App\Support\PermissionCache;
use Illuminate\Database\Eloquent\SoftDeletes;

trait InvalidatesBoilerplateCaches
{
    protected static function bootInvalidatesBoilerplateCaches(): void
    {
        $flush = static function (): void {
            PermissionCache::flush();
            DashboardCache::flush();
        };

        static::saved($flush);
        static::deleted($flush);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored($flush);
        }
    }
}
