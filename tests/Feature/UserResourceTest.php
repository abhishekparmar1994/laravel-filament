<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
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
                'roles' => [$editorRole->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame('Jane Doe', $created->name);
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
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_can_delete_user(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertModelMissing($user);
    }

    public function test_admin_panel_has_spa_mode_enabled(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasSpaMode());
        $this->assertTrue($panel->hasSpaPrefetching());
    }
}
