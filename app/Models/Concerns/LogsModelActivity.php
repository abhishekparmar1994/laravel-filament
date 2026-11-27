<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;

trait LogsModelActivity
{
    protected static function bootLogsModelActivity(): void
    {
        static::created(function (Model $model): void {
            static::recordActivity($model, 'created', [
                'attributes' => static::sanitizeActivityAttributes($model->getAttributes()),
            ]);
        });

        static::updated(function (Model $model): void {
            $changes = static::sanitizeActivityAttributes($model->getChanges());
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $old = static::sanitizeActivityAttributes(
                Arr::only($model->getOriginal(), array_keys($changes)),
            );

            static::recordActivity($model, 'updated', [
                'old' => $old,
                'attributes' => $changes,
            ]);
        });

        static::deleted(function (Model $model): void {
            static::recordActivity($model, 'deleted', [
                'attributes' => static::sanitizeActivityAttributes($model->getAttributes()),
            ]);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                static::recordActivity($model, 'restored');
            });
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function recordActivity(Model $model, string $event, array $properties = []): void
    {
        $label = class_basename($model);
        $subjectName = $model->getAttribute('name') ?? $model->getAttribute('key');
        $identifier = filled($subjectName) ? "\"{$subjectName}\" (#{$model->getKey()})" : "#{$model->getKey()}";

        try {
            ActivityLog::query()->create([
                'user_id' => auth()->id(),
                'event' => $event,
                'subject_type' => $model::class,
                'subject_id' => $model->getKey(),
                'description' => "{$label} {$identifier} was {$event}",
                'properties' => $properties === [] ? null : $properties,
                'ip_address' => request()?->ip(),
            ]);
        } catch (QueryException) {
            // Skip logging cleanly if migrations for activity_logs have not run yet.
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function sanitizeActivityAttributes(array $attributes): array
    {
        return Arr::except($attributes, [
            'password',
            'remember_token',
        ]);
    }
}
