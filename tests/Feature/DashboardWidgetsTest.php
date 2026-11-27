<?php

namespace Tests\Feature;

use App\Filament\Widgets\LatestUsersTable;
use App\Filament\Widgets\SystemStatsOverview;
use App\Filament\Widgets\UserGrowthChart;
use App\Filament\Widgets\UsersByRoleChart;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_filament_dashboard_widgets_are_removed(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
        $this->assertContains(SystemStatsOverview::class, $widgets);
        $this->assertContains(UserGrowthChart::class, $widgets);
        $this->assertContains(UsersByRoleChart::class, $widgets);
        $this->assertContains(LatestUsersTable::class, $widgets);
    }

    public function test_custom_dashboard_widgets_render_for_authorized_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create(['name' => 'Dashboard Admin']);
        $admin->roles()->attach($superAdminRole);

        $this->actingAs($admin);

        Livewire::test(SystemStatsOverview::class)
            ->assertOk()
            ->assertSee('Total Users')
            ->assertSee('Active Roles')
            ->assertSee('Permissions');

        Livewire::test(UserGrowthChart::class)
            ->assertOk()
            ->assertSee('User Signups (Last 7 Days)');

        Livewire::test(UsersByRoleChart::class)
            ->assertOk()
            ->assertSee('Users by Role');

        Livewire::test(LatestUsersTable::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$admin]);
    }

    public function test_latest_users_table_widget_is_hidden_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertFalse(LatestUsersTable::canView());
    }
}
