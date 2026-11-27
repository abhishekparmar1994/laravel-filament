<?php

namespace Tests\Feature;

use App\Filament\Widgets\SystemStatsOverview;
use App\Filament\Widgets\UserGrowthChart;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\DashboardCache;
use App\Support\PermissionCache;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_and_setting_caches_eliminate_repeated_sql_queries(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('slug', 'admin')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        Setting::setValue('site_name', 'Fast Admin');

        // Warm caches once.
        $this->assertTrue($user->hasPermission('users.view_any'));
        $this->assertSame('Fast Admin', Setting::getValue('site_name'));

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermission('users.create'));
        $this->assertFalse($user->hasPermission('settings.manage'));
        $this->assertSame('Fast Admin', Setting::getValue('site_name'));

        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_permission_cache_invalidates_when_flushed_or_role_updated(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($viewerRole);

        $this->assertFalse($user->hasPermission('settings.manage'));

        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $user->roles()->attach($superAdminRole);
        PermissionCache::flush();

        $this->assertTrue($user->hasPermission('settings.manage'));
    }

    public function test_dashboard_cache_serves_cached_metrics_and_invalidates_on_user_creation(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($role);
        $this->actingAs($admin);

        Livewire::test(SystemStatsOverview::class)->assertOk();
        UserGrowthChart::dailySignups(7);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $cachedSeries = UserGrowthChart::dailySignups(7);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, array_sum($cachedSeries));

        // Creating a new user must automatically bust DashboardCache.
        User::factory()->create();
        DashboardCache::remember('probe', fn (): string => 'fresh');

        $updatedSeries = UserGrowthChart::dailySignups(7);
        $this->assertSame(2, array_sum($updatedSeries));
    }
}
