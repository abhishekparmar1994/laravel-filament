<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $totalRoles = Role::query()->count();
        $assignedUsers = User::query()->has('roles')->count();
        $totalPermissions = Permission::query()->count();
        $permissionGroups = Permission::query()
            ->whereNotNull('group')
            ->distinct('group')
            ->count('group');

        $dailySignups = array_values(UserGrowthChart::dailySignups(7));

        return [
            Stat::make('Total Users', $totalUsers)
                ->description("{$verifiedUsers} verified accounts")
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->icon(Heroicon::OutlinedUsers)
                ->chart($dailySignups)
                ->color('primary')
                ->url(UserResource::canViewAny() ? UserResource::getUrl() : null),
            Stat::make('Active Roles', $totalRoles)
                ->description("{$assignedUsers} users assigned")
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('success')
                ->url(RoleResource::canViewAny() ? RoleResource::getUrl() : null),
            Stat::make('Permissions', $totalPermissions)
                ->description("Across {$permissionGroups} groups")
                ->descriptionIcon(Heroicon::OutlinedSquares2x2)
                ->icon(Heroicon::OutlinedKey)
                ->color('info')
                ->url(PermissionResource::canViewAny() ? PermissionResource::getUrl() : null),
        ];
    }
}
