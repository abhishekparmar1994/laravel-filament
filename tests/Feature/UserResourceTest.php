<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $role = Role::factory()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);

        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        return $admin;
    }

    public function test_can_list_users(): void
    {
        $admin = $this->createSuperAdmin();
        $users = User::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    }

    public function test_can_create_user_with_roles(): void
    {
        $admin = $this->createSuperAdmin();
        $editorRole = Role::factory()->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'secret1234',
                'is_active' => true,
                'roles' => [$editorRole->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame('Jane Doe', $created->name);
        $this->assertTrue($created->is_active);
        $this->assertTrue(Hash::check('secret1234', $created->password));
        $this->assertTrue($created->hasRole('editor'));
    }

    public function test_can_edit_user_without_overwriting_password(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'original123',
        ]);
        $originalHash = $user->password;

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'New Name',
                'email' => 'new@example.com',
                'password' => null,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertFalse($user->is_active);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_can_soft_delete_and_restore_user(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($user);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction(RestoreAction::class);

        $this->assertNotSoftDeleted($user->fresh());
    }

    public function test_inactive_user_cannot_access_panel_and_login_updates_timestamp(): void
    {
        $role = Role::factory()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);

        $inactiveUser = User::factory()->inactive()->create();
        $inactiveUser->roles()->attach($role);

        $activeUser = User::factory()->create(['last_login_at' => null]);
        $activeUser->roles()->attach($role);

        $panel = Filament::getPanel('admin');

        $this->assertFalse($inactiveUser->canAccessPanel($panel));
        $this->assertTrue($activeUser->canAccessPanel($panel));

        event(new Login('web', $activeUser, false));

        $this->assertNotNull($activeUser->fresh()->last_login_at);
    }

    public function test_admin_panel_account_features_and_global_search_are_configured(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasSpaMode());
        $this->assertTrue($panel->hasSpaPrefetching());
        $this->assertTrue($panel->hasProfile());
        $this->assertTrue($panel->hasPasswordReset());
        $this->assertSame(['name', 'email'], UserResource::getGloballySearchableAttributes());
    }
}
