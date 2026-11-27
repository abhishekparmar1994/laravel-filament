<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSessions;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Role;
use App\Models\User;
use App\Support\SystemNotifier;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_admins_targets_only_active_super_admins(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();

        $activeAdmin = User::factory()->create();
        $activeAdmin->roles()->attach($superAdminRole);

        $inactiveAdmin = User::factory()->inactive()->create();
        $inactiveAdmin->roles()->attach($superAdminRole);

        $viewer = User::factory()->create();
        $viewer->roles()->attach($viewerRole);

        SystemNotifier::notifyAdmins('System Alert', 'Important notice.', 'warning');

        $this->assertSame(1, $activeAdmin->notifications()->count());
        $this->assertSame(0, $inactiveAdmin->notifications()->count());
        $this->assertSame(0, $viewer->notifications()->count());
    }

    public function test_creating_user_sends_welcome_and_admin_database_notifications(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($superAdminRole);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Notified User',
                'email' => 'notified@example.com',
                'password' => 'secret1234',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'notified@example.com')->firstOrFail();

        $this->assertSame(1, $created->notifications()->count());
    }

    public function test_login_session_revocation_and_test_notification_action_create_database_notifications(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($superAdminRole);

        event(new Login('web', $admin, false));
        $this->assertSame(1, $admin->notifications()->count());

        $this->actingAs($admin);

        Livewire::test(ManageSettings::class)
            ->callAction('sendTestNotification')
            ->assertHasNoActionErrors();

        $this->assertSame(2, $admin->fresh()->notifications()->count());

        DB::table('sessions')->insert([
            'id' => 'extra-session',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser/1.0',
            'payload' => 'dGVzdA==',
            'last_activity' => now()->timestamp,
        ]);

        Livewire::test(ManageSessions::class)->call('revokeOtherSessions');

        $this->assertSame(3, $admin->fresh()->notifications()->count());
    }

    public function test_notifications_are_stored_immediately_even_with_database_queue_driver(): void
    {
        $this->seed(RolePermissionSeeder::class);

        config()->set('queue.default', 'database');

        $user = User::factory()->create();
        SystemNotifier::notifyUser($user, 'Sync Delivery', 'Written directly to notifications.', 'info');

        $this->assertSame(1, $user->notifications()->count());
        $this->assertDatabaseCount('jobs', 0);
    }
}
