<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogAndSettingsTest extends TestCase
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

    public function test_user_mutations_record_activity_logs_without_exposing_password(): void
    {
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);

        $created = User::factory()->create([
            'name' => 'Audit Target',
            'password' => 'top-secret-password',
        ]);

        $createdLog = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $created->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($createdLog);
        $this->assertArrayNotHasKey('password', $createdLog->properties['attributes'] ?? []);
        $this->assertArrayNotHasKey('remember_token', $createdLog->properties['attributes'] ?? []);

        $created->update(['name' => 'Audit Target Updated']);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => User::class,
            'subject_id' => $created->id,
            'event' => 'updated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_activity_log_page_is_accessible_to_super_admin_and_blocked_for_viewer(): void
    {
        $admin = $this->createSuperAdmin();
        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();
        $viewer = User::factory()->create();
        $viewer->roles()->attach($viewerRole);

        $this->actingAs($admin);
        Livewire::test(ListActivityLogs::class)->assertOk();

        $this->actingAs($viewer);
        Livewire::test(ListActivityLogs::class)->assertForbidden();
    }

    public function test_settings_page_saves_values_and_enforces_permission(): void
    {
        $admin = $this->createSuperAdmin();
        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();
        $viewer = User::factory()->create();
        $viewer->roles()->attach($viewerRole);

        $this->actingAs($viewer);
        Livewire::test(ManageSettings::class)->assertForbidden();

        $this->actingAs($admin);
        Livewire::test(ManageSettings::class)
            ->fillForm([
                'site_name' => 'Acme Admin',
                'site_tagline' => 'Operations Hub',
                'support_email' => 'ops@acme.test',
                'maintenance_banner' => 'Scheduled maintenance tonight.',
                'registration_enabled' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Acme Admin', Setting::getValue('site_name'));
        $this->assertSame('1', Setting::getValue('registration_enabled'));
        $this->assertTrue(Filament::getPanel('admin')->hasDatabaseNotifications());
    }
}
