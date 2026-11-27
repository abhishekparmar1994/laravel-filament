<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class ManageSessions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Preferences';

    protected static ?string $navigationLabel = 'Active Sessions';

    protected static ?int $navigationSort = 21;

    protected static ?string $title = 'Active Browser Sessions';

    protected string $view = 'filament.pages.manage-sessions';

    /**
     * @return Collection<int, array{id: string, ip_address: string, user_agent: string, last_active: string, is_current: bool}>
     */
    public function getSessions(): Collection
    {
        if (! Schema::hasTable('sessions')) {
            return collect();
        }

        $currentId = session()->getId();

        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session): array => [
                'id' => (string) $session->id,
                'ip_address' => (string) ($session->ip_address ?: 'Unknown'),
                'user_agent' => (string) ($session->user_agent ?: 'Unknown browser'),
                'last_active' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                'is_current' => (string) $session->id === $currentId,
            ]);
    }

    public function revokeSession(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            return;
        }

        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', $sessionId)
            ->delete();

        Notification::make()
            ->title('Session revoked')
            ->success()
            ->send();
    }

    public function revokeOtherSessions(): void
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        Notification::make()
            ->title('All other browser sessions revoked')
            ->success()
            ->send();
    }
}
