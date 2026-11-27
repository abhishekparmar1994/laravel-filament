@php
    use App\Models\Setting;
    use App\Support\ImpersonationManager;

    $isImpersonating = ImpersonationManager::isImpersonating();
    $maintenanceBanner = trim((string) Setting::getValue('maintenance_banner', ''));
@endphp

@if ($isImpersonating)
    <div class="flex flex-wrap items-center justify-between gap-3 bg-amber-500 px-4 py-2 text-sm font-medium text-gray-950">
        <span>
            Impersonating <strong>{{ auth()->user()?->name }}</strong> ({{ auth()->user()?->email }})
        </span>
        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            <button
                type="submit"
                class="rounded-full bg-gray-950 px-3 py-1 text-xs font-semibold text-white transition hover:bg-gray-800"
            >
                Stop Impersonating
            </button>
        </form>
    </div>
@endif

@if ($maintenanceBanner !== '')
    <div class="border-b border-primary-200 bg-primary-50 px-4 py-2 text-center text-xs font-medium text-primary-900 dark:border-primary-900/50 dark:bg-primary-950/60 dark:text-primary-200">
        {{ $maintenanceBanner }}
    </div>
@endif
