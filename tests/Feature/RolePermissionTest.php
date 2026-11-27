<?php

namespace Tests\Feature;

use App\Filament\Resources\Permissions\Pages\CreatePermission;
use App\Filament\Resources\Permissions\Pages\EditPermission;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($superAdminRole);

        return $admin;
    }

    public function test_seeder_creates_default_roles_and_permissions(): void
    {
        $firstUser = User::factory()->create();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('roles', ['slug' => 'super-admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'viewer']);
        $this->assertDatabaseHas('permissions', ['slug' => 'users.view_any']);
        $this->assertTrue($firstUser->fresh()->hasRole('super-admin'));
    }

    public function test_super_admin_can_crud_roles_with_permissions(): void
    {
        $admin = $this->createSuperAdmin();
        $permission = Permission::where('slug', 'users.view_any')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(ListRoles::class)
            ->assertOk();

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'Support Agent',
                'slug' => 'support-agent',
                'description' => 'Handles customer support inquiries.',
                'permissions' => [$permission->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::where('slug', 'support-agent')->firstOrFail();
        $this->assertTrue($role->permissions()->where('slug', 'users.view_any')->exists());

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm(['name' => 'Support Lead'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Support Lead', $role->fresh()->name);

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($role);
    }

    public function test_super_admin_can_crud_permissions(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin);

        Livewire::test(ListPermissions::class)
            ->assertOk();

        Livewire::test(CreatePermission::class)
            ->fillForm([
                'name' => 'Export Reports',
                'slug' => 'reports.export',
                'group' => 'reports',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $permission = Permission::where('slug', 'reports.export')->firstOrFail();

        Livewire::test(EditPermission::class, ['record' => $permission->getRouteKey()])
            ->fillForm(['name' => 'Download Reports'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Download Reports', $permission->fresh()->name);

        Livewire::test(EditPermission::class, ['record' => $permission->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($permission);
    }

    public function test_viewer_role_is_restricted_by_policies(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();
        $viewer = User::factory()->create();
        $viewer->roles()->attach($viewerRole);

        $this->actingAs($viewer);

        Livewire::test(ListUsers::class)->assertOk();
        Livewire::test(CreateUser::class)->assertForbidden();
        Livewire::test(ListRoles::class)->assertForbidden();
        Livewire::test(ListPermissions::class)->assertForbidden();
    }

    public function test_safety_guards_prevent_self_and_super_admin_role_deletion(): void
    {
        $admin = $this->createSuperAdmin();
        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $guest = User::factory()->create();
        $panel = Filament::getPanel('admin');

        $this->assertFalse($admin->can('delete', $admin));
        $this->assertFalse($admin->can('delete', $superAdminRole));
        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($guest->canAccessPanel($panel));
    }
}
