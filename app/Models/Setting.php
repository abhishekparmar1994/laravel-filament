<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Throwable;

#[Fillable(['key', 'value', 'group', 'type'])]
class Setting extends Model
{
    use LogsModelActivity;

    private const CACHE_PREFIX = 'app_setting:';

    /**
     * Retrieve a setting value by key from cache, querying only on cache miss.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            $cached = Cache::rememberForever(
                self::CACHE_PREFIX.$key,
                fn (): mixed => static::query()->where('key', $key)->value('value'),
            );

            return $cached ?? $default;
        } catch (QueryException|Throwable) {
            return $default;
        }
    }

    /**
     * Persist a setting value and refresh its cache entry.
     */
    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        $normalized = is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '');

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $normalized,
                'group' => $group,
                'type' => $type,
            ],
        );

        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }
}
