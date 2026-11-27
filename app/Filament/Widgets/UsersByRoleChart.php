<?php

namespace App\Filament\Widgets;

use App\Models\Role;
use App\Models\User;
use App\Support\DashboardCache;
use Filament\Widgets\ChartWidget;

class UsersByRoleChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Users by Role';

    protected ?string $description = 'Distribution of assigned roles across accounts.';

    protected ?string $maxHeight = '260px';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $distribution = DashboardCache::remember('users_by_role', function (): array {
            $roles = Role::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->get(['id', 'name']);

            $labels = $roles->pluck('name')->all();
            $counts = $roles->pluck('users_count')->map(fn (mixed $count): int => (int) $count)->all();

            $unassignedCount = User::query()->doesntHave('roles')->count();

            if ($unassignedCount > 0 || empty($labels)) {
                $labels[] = 'Unassigned';
                $counts[] = $unassignedCount;
            }

            return [
                'labels' => $labels,
                'counts' => $counts,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $distribution['counts'],
                    'backgroundColor' => [
                        '#3a664b',
                        '#719f82',
                        '#9ec0aa',
                        '#c6dacc',
                        '#78716c',
                    ],
                ],
            ],
            'labels' => $distribution['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
