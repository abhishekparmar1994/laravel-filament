<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Support\DashboardCache;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UserGrowthChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'User Signups (Last 7 Days)';

    protected ?string $description = 'Daily new account registrations.';

    protected ?string $maxHeight = '260px';

    protected string $color = 'primary';

    /**
     * Return a date-indexed map of user registrations for the last N days.
     *
     * @return array<string, int>
     */
    public static function dailySignups(int $days = 7): array
    {
        return DashboardCache::remember("daily_signups:{$days}", function () use ($days): array {
            $start = now()->subDays($days - 1)->startOfDay();

            $countsByDate = User::query()
                ->where('created_at', '>=', $start)
                ->get(['created_at'])
                ->groupBy(fn (User $user): string => Carbon::parse($user->created_at)->format('Y-m-d'))
                ->map->count();

            $series = [];

            for ($offset = 0; $offset < $days; $offset++) {
                $date = $start->copy()->addDays($offset);
                $series[$date->format('M j')] = (int) ($countsByDate[$date->format('Y-m-d')] ?? 0);
            }

            return $series;
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $signups = static::dailySignups(7);

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => array_values($signups),
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
            ],
            'labels' => array_keys($signups),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
