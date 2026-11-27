<x-filament-panels::page>
    @php
        $sessions = $this->getSessions();
    @endphp

    <x-filament::section
        heading="Signed-in Devices"
        description="Manage active sessions across your browsers and devices. Revoke any session you do not recognize."
    >
        <div class="space-y-4">
            @forelse ($sessions as $session)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200/80 p-4 dark:border-white/10">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                {{ $session['ip_address'] }}
                            </span>
                            @if ($session['is_current'])
                                <x-filament::badge color="success">
                                    This device
                                </x-filament::badge>
                            @endif
                        </div>
                        <p class="max-w-2xl truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $session['user_agent'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Last active {{ $session['last_active'] }}
                        </p>
                    </div>

                    @unless ($session['is_current'])
                        <x-filament::button
                            color="danger"
                            size="sm"
                            wire:click="revokeSession('{{ $session['id'] }}')"
                        >
                            Revoke
                        </x-filament::button>
                    @endunless
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No database-backed sessions found for your account. (Sessions appear here when SESSION_DRIVER=database.)
                </p>
            @endforelse

            @if ($sessions->where('is_current', false)->isNotEmpty())
                <div class="pt-2">
                    <x-filament::button
                        color="danger"
                        wire:click="revokeOtherSessions"
                    >
                        Log Out Other Browser Sessions
                    </x-filament::button>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
