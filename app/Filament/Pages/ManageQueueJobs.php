<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use UnitEnum;

class ManageQueueJobs extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Queue & Jobs';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Queue & Failed Jobs Monitor';

    protected string $view = 'filament.pages.manage-queue-jobs';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('system.jobs') ?? false;
    }

    public function getPendingCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')->count();
    }

    /**
     * @return Collection<int, array{id: int, uuid: string, connection: string, queue: string, job_name: string, exception_summary: string, failed_at: string}>
     */
    public function getFailedJobs(): Collection
    {
        if (! Schema::hasTable('failed_jobs')) {
            return collect();
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(25)
            ->get()
            ->map(function (object $job): array {
                $payload = json_decode((string) $job->payload, true);
                $displayName = is_array($payload) ? ($payload['displayName'] ?? 'Queued Job') : 'Queued Job';

                return [
                    'id' => (int) $job->id,
                    'uuid' => (string) $job->uuid,
                    'connection' => (string) $job->connection,
                    'queue' => (string) $job->queue,
                    'job_name' => (string) $displayName,
                    'exception_summary' => Str::limit(strtok((string) $job->exception, "\n") ?: 'Failed', 140),
                    'failed_at' => Carbon::parse((string) $job->failed_at)->diffForHumans(),
                ];
            });
    }

    public function retryFailedJob(int $id): void
    {
        if (! static::canAccess()) {
            abort(403);
        }

        $job = DB::table('failed_jobs')->where('id', $id)->first();

        if ($job === null) {
            return;
        }

        Queue::connection((string) $job->connection)->pushRaw((string) $job->payload, (string) $job->queue);
        DB::table('failed_jobs')->where('id', $id)->delete();

        Notification::make()
            ->title("Re-queued failed job #{$id}")
            ->success()
            ->send();
    }

    public function deleteFailedJob(int $id): void
    {
        if (! static::canAccess()) {
            abort(403);
        }

        DB::table('failed_jobs')->where('id', $id)->delete();

        Notification::make()
            ->title("Deleted failed job #{$id}")
            ->success()
            ->send();
    }

    public function flushFailedJobs(): void
    {
        if (! static::canAccess()) {
            abort(403);
        }

        DB::table('failed_jobs')->truncate();

        Notification::make()
            ->title('Cleared all failed jobs')
            ->success()
            ->send();
    }
}
