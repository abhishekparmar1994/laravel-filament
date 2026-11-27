<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;
use UnitEnum;

class ManageSystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Health & Logs';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'System Health & Application Logs';

    protected string $view = 'filament.pages.manage-system-health';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('system.logs') ?? false;
    }

    /**
     * @return array<int, array{label: string, value: string, healthy: bool}>
     */
    public function getHealthChecks(): array
    {
        $dbHealthy = true;
        $dbLabel = (string) config('database.default');

        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $dbHealthy = false;
        }

        $cacheHealthy = true;

        try {
            Cache::put('health_check_probe', 'ok', 5);
            $cacheHealthy = Cache::get('health_check_probe') === 'ok';
        } catch (Throwable) {
            $cacheHealthy = false;
        }

        $freeBytes = @disk_free_space(base_path());
        $diskSummary = is_numeric($freeBytes)
            ? number_format($freeBytes / 1024 / 1024 / 1024, 2).' GB free'
            : 'Unavailable';

        return [
            ['label' => 'Database', 'value' => $dbHealthy ? "Connected ({$dbLabel})" : 'Connection error', 'healthy' => $dbHealthy],
            ['label' => 'Cache Store', 'value' => $cacheHealthy ? 'Operational ('.config('cache.default').')' : 'Cache error', 'healthy' => $cacheHealthy],
            ['label' => 'PHP / Laravel', 'value' => 'PHP '.PHP_VERSION.' / Laravel '.app()->version(), 'healthy' => true],
            ['label' => 'Disk Space', 'value' => $diskSummary, 'healthy' => is_numeric($freeBytes) ? $freeBytes > 100 * 1024 * 1024 : true],
        ];
    }

    public function getLogTail(int $maxLines = 80): string
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            return 'No log file found at storage/logs/laravel.log.';
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES) ?: [];

        if ($lines === []) {
            return 'Log file is empty.';
        }

        return implode("\n", array_slice($lines, -$maxLines));
    }

    public function clearLogs(): void
    {
        if (! static::canAccess()) {
            abort(403);
        }

        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        Notification::make()
            ->title('Application log cleared')
            ->success()
            ->send();
    }
}
