<x-filament-panels::page>
    @php
        $pendingCount = $this->getPendingCount();
        $failedJobs = $this->getFailedJobs();
    @endphp

    <div class="grid gap-4 sm:grid-cols-2">
        <x-filament::section heading="Pending Jobs">
            <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $pendingCount }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Queue connection: {{ config('queue.default') }}
            </p>
        </x-filament::section>

        <x-filament::section heading="Failed Jobs">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-3xl font-semibold text-gray-950 dark:text-white">{{ $failedJobs->count() }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Showing up to 25 most recent failures</p>
                </div>

                @if ($failedJobs->isNotEmpty())
                    <x-filament::button color="danger" size="sm" wire:click="flushFailedJobs">
                        Flush All
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>
    </div>

    <x-filament::section heading="Recent Failed Jobs">
        <div class="space-y-3">
            @forelse ($failedJobs as $job)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200/80 p-4 dark:border-white/10">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $job['job_name'] }}</span>
                            <x-filament::badge color="gray">{{ $job['queue'] }}</x-filament::badge>
                        </div>
                        <p class="text-xs text-danger-600 dark:text-danger-400">{{ $job['exception_summary'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Failed {{ $job['failed_at'] }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::button size="sm" wire:click="retryFailedJob({{ $job['id'] }})">
                            Retry
                        </x-filament::button>
                        <x-filament::button color="danger" size="sm" wire:click="deleteFailedJob({{ $job['id'] }})">
                            Delete
                        </x-filament::button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No failed jobs recorded.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
