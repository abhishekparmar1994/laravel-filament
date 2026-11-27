<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->getHealthChecks() as $check)
            <x-filament::section :heading="$check['label']">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $check['value'] }}</span>
                    <x-filament::badge :color="$check['healthy'] ? 'success' : 'danger'">
                        {{ $check['healthy'] ? 'OK' : 'Issue' }}
                    </x-filament::badge>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Application Log (Last 80 Lines)" description="storage/logs/laravel.log">
        <div class="space-y-3">
            <div class="flex justify-end">
                <x-filament::button color="danger" size="sm" wire:click="clearLogs">
                    Clear Log
                </x-filament::button>
            </div>

            <pre
                class="max-h-96 overflow-auto rounded-xl bg-gray-950 p-4 text-xs leading-relaxed text-gray-100">{{ $this->getLogTail() }}</pre>
        </div>
    </x-filament::section>
</x-filament-panels::page>