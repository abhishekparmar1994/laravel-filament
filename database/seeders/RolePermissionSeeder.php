<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\DashboardCache;
use App\Support\PermissionCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed default roles, permissions, settings, and assign Super Admin to the first user if none exists.
     */
    public function run(): void
    {
        $permissionDefinitions = [
            ['name' => 'Access Admin Panel', 'slug' => 'panel.access', 'group' => 'panel'],

            ['name' => 'View Users', 'slug' => 'users.view_any', 'group' => 'users'],
            ['name' => 'View User Details', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
            ['name' => 'Impersonate Users', 'slug' => 'users.impersonate', 'group' => 'users'],

            ['name' => 'View Roles', 'slug' => 'roles.view_any', 'group' => 'roles'],
            ['name' => 'View Role Details', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'roles'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'group' => 'roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'roles'],

            ['name' => 'View Permissions', 'slug' => 'permissions.view_any', 'group' => 'permissions'],
            ['name' => 'View Permission Details', 'slug' => 'permissions.view', 'group' => 'permissions'],
            ['name' => 'Create Permissions', 'slug' => 'permissions.create', 'group' => 'permissions'],
            ['name' => 'Update Permissions', 'slug' => 'permissions.update', 'group' => 'permissions'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'group' => 'permissions'],

            ['name' => 'View Activity Logs', 'slug' => 'activity_logs.view_any', 'group' => 'system'],
            ['name' => 'View Activity Log Details', 'slug' => 'activity_logs.view', 'group' => 'system'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'system'],
            ['name' => 'Manage Queue Jobs', 'slug' => 'system.jobs', 'group' => 'system'],
            ['name' => 'View System Health & Logs', 'slug' => 'system.logs', 'group' => 'system'],
        ];

        $permissionsBySlug = collect($permissionDefinitions)
            ->mapWithKeys(function (array $definition): array {
                $permission = Permission::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    ['name' => $definition['name'], 'group' => $definition['group']],
                );

                return [$definition['slug'] => $permission->id];
            });

        $superAdmin = Role::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full unrestricted access to all resources and settings.'],
        );
        $superAdmin->permissions()->sync($permissionsBySlug->values()->all());

        $admin = Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Manage users and view roles, permissions, and audit logs.'],
        );
        $admin->permissions()->sync([
            $permissionsBySlug['panel.access'],
            $permissionsBySlug['users.view_any'],
            $permissionsBySlug['users.view'],
            $permissionsBySlug['users.create'],
            $permissionsBySlug['users.update'],
            $permissionsBySlug['roles.view_any'],
            $permissionsBySlug['roles.view'],
            $permissionsBySlug['permissions.view_any'],
            $permissionsBySlug['permissions.view'],
            $permissionsBySlug['activity_logs.view_any'],
            $permissionsBySlug['activity_logs.view'],
        ]);

        $viewer = Role::query()->updateOrCreate(
            ['slug' => 'viewer'],
            ['name' => 'Viewer', 'description' => 'Read-only access to the admin panel and users list.'],
        );
        $viewer->permissions()->sync([
            $permissionsBySlug['panel.access'],
            $permissionsBySlug['users.view_any'],
            $permissionsBySlug['users.view'],
        ]);

        $hasSuperAdmin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))
            ->exists();

        if (! $hasSuperAdmin) {
            User::query()->oldest('id')->first()?->roles()->syncWithoutDetaching([$superAdmin->id]);
        }

        if (Schema::hasTable('settings')) {
            $defaults = [
                ['key' => 'site_name', 'value' => config('app.name', 'Laravel'), 'group' => 'general', 'type' => 'string'],
                ['key' => 'site_tagline', 'value' => 'Startup Admin Boilerplate', 'group' => 'general', 'type' => 'string'],
                ['key' => 'support_email', 'value' => 'support@example.com', 'group' => 'general', 'type' => 'string'],
                ['key' => 'maintenance_banner', 'value' => '', 'group' => 'system', 'type' => 'string'],
                ['key' => 'registration_enabled', 'value' => '0', 'group' => 'auth', 'type' => 'boolean'],
            ];

            foreach ($defaults as $default) {
                if (! Setting::query()->where('key', $default['key'])->exists()) {
                    Setting::setValue($default['key'], $default['value'], $default['group'], $default['type']);
                }
            }
        }

        PermissionCache::flush();
        DashboardCache::flush();
    }
}
