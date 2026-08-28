<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'access admin panel',
            'manage vehicles',
            'manage drivers',
            'confirm boarding',
            'book trips',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var array<string, array<int, string>> $rolePermissions */
        $rolePermissions = [
            UserRole::Passenger->value => ['book trips'],
            UserRole::Driver->value => ['confirm boarding'],
            UserRole::Transporter->value => ['manage vehicles', 'manage drivers'],
            UserRole::Admin->value => $permissions,
        ];

        foreach ($rolePermissions as $roleName => $grantedPermissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($grantedPermissions);
        }
    }
}
