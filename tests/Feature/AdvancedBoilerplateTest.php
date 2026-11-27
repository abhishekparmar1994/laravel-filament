<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageAppearance;
use App\Filament\Pages\ManageQueueJobs;
use App\Filament\Pages\ManageSessions;
use App\Filament\Pages\ManageSettings;
use App\Filament\Pages\ManageSystemHealth;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Http\Middleware\ApplyUserLocale;
use App\Http\Middleware\SecurityHeaders;
use App\Mail\TestMail;
use App\Models\Role;
use App\Models\User;
use App\Support\ImpersonationManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdvancedBoilerplateTest extends TestCase
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

    public function test_panel_enables_multi_factor_authentication_and_security_headers(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasMultiFactorAuthentication());

        $request = Request::create('/admin');
        $response = (new SecurityHeaders)->handle($request, fn (): Response => response('ok'));

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_super_admin_can_impersonate_active_user_and_stop_impersonating(): void
    {
        $admin = $this->createSuperAdmin();
        $target = User::factory()->create(['email' => 'target@example.com']);
        $inactive = User::factory()->inactive()->create();

        $this->assertFalse(ImpersonationManager::canImpersonate($admin, $admin));
        $this->assertFalse(ImpersonationManager::canImpersonate($admin, $inactive));
        $this->assertTrue(ImpersonationManager::canImpersonate($admin, $target));

        $this->actingAs($admin);
        ImpersonationManager::start($admin, $target);

        $this->assertTrue(ImpersonationManager::isImpersonating());
        $this->assertSame($target->id, auth()->id());
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'impersonated',
            'user_id' => $admin->id,
            'subject_id' => $target->id,
        ]);

        $response = $this->post(route('impersonation.stop'));

        $response->assertRedirect('/admin');
        $this->assertFalse(ImpersonationManager::isImpersonating());
        $this->assertSame($admin->id, auth()->id());
    }

    public function test_user_can_switch_locale_and_middleware_applies_it(): void
    {
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);

        Livewire::test(ManageAppearance::class)
            ->fillForm([
                'theme' => 'modern',
                'locale' => 'es',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $updated = $admin->fresh();
        $this->assertSame('modern', $updated->theme);
        $this->assertSame('es', $updated->locale);

        app()->setLocale('en');
        $request = Request::create('/admin');
        $request->setUserResolver(fn (): User => $updated);

        (new ApplyUserLocale)->handle($request, fn (): Response => response('ok'));
        $this->assertSame('es', app()->getLocale());
    }

    public function test_user_can_revoke_other_database_sessions(): void
    {
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);

        DB::table('sessions')->insert([
            'id' => 'other-device-session',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestBrowser/1.0',
            'payload' => 'dGVzdA==',
            'last_activity' => now()->timestamp,
        ]);

        Livewire::test(ManageSessions::class)
            ->assertOk()
            ->assertSee('TestBrowser/1.0')
            ->call('revokeOtherSessions');

        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    }

    public function test_queue_monitor_and_system_health_enforce_permissions_and_manage_failed_jobs(): void
    {
        $admin = $this->createSuperAdmin();
        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();
        $viewer = User::factory()->create();
        $viewer->roles()->attach($viewerRole);

        $this->actingAs($viewer);
        Livewire::test(ManageQueueJobs::class)->assertForbidden();
        Livewire::test(ManageSystemHealth::class)->assertForbidden();

        $this->actingAs($admin);
        Livewire::test(ManageSystemHealth::class)
            ->assertOk()
            ->assertSee('Database')
            ->assertSee('Cache Store');

        $id = DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\DemoJob']),
            'exception' => 'RuntimeException: Something broke',
            'failed_at' => now(),
        ]);

        Queue::fake();

        Livewire::test(ManageQueueJobs::class)
            ->assertOk()
            ->assertSee('App\\Jobs\\DemoJob')
            ->call('retryFailedJob', $id);

        $this->assertDatabaseMissing('failed_jobs', ['id' => $id]);
    }

    public function test_settings_can_dispatch_test_email_and_users_can_be_imported_from_csv(): void
    {
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin);

        Mail::fake();

        Livewire::test(ManageSettings::class)
            ->callAction('sendTestEmail', data: ['recipient' => 'check@example.com'])
            ->assertHasNoActionErrors();

        Mail::assertSent(TestMail::class, fn (TestMail $mail): bool => $mail->hasTo('check@example.com'));

        $csv = "name,email,password\nAlice Example,alice@example.com,pass12345\nDuplicate,alice@example.com,pass12345\nInvalid Row,not-an-email,pass12345";
        $summary = ListUsers::importFromCsvContent($csv);

        $this->assertSame(['imported' => 1, 'skipped' => 2], $summary);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com', 'name' => 'Alice Example']);
    }
}
