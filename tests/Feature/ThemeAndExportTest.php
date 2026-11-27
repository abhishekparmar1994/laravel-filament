<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageAppearance;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Http\Middleware\ApplyUserTheme;
use App\Models\Role;
use App\Models\User;
use App\Support\ThemeManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ThemeAndExportTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        return $admin;
    }

    public function test_user_can_change_and_apply_theme_preset(): void
    {
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);

        Livewire::test(ManageAppearance::class)
            ->fillForm(['theme' => 'rose'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('rose', $admin->fresh()->theme);

        $request = Request::create('/admin');
        $request->setUserResolver(fn (): User => $admin->fresh());

        $response = (new ApplyUserTheme)->handle($request, fn (): Response => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('primary', FilamentColor::getColors());
        $this->assertSame(ThemeManager::DEFAULT_THEME, ThemeManager::resolveForUser(User::factory()->make(['theme' => 'unknown'])));
    }

    public function test_can_view_user_infolist_and_filter_users_table(): void
    {
        $admin = $this->createSuperAdmin();
        $inactive = User::factory()->inactive()->create(['name' => 'Suspended User']);
        $active = User::factory()->create(['name' => 'Active Member']);

        $this->actingAs($admin);

        Livewire::test(ViewUser::class, ['record' => $active->getRouteKey()])
            ->assertOk()
            ->assertSee('Active Member');

        Livewire::test(ListUsers::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_can_export_users_as_csv(): void
    {
        $admin = $this->createSuperAdmin();
        User::factory()->create([
            'name' => 'CSV Export Target',
            'email' => 'csv@example.com',
        ]);

        $this->actingAs($admin);

        $response = ListUsers::exportCsv();

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertIsString($content);
        $this->assertStringContainsString('CSV Export Target', $content);
        $this->assertStringContainsString('csv@example.com', $content);
    }
}
