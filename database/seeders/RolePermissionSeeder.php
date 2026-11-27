<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed default roles, permissions, and assign Super Admin to the first user if none exists.
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
            ['name' => 'Admin', 'description' => 'Manage users and view roles and permissions.'],
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
    }
}
