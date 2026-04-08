<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    // Crea roles base usados por authorization en la API.
    public function run(): void
    {
        $adminRole = Role::findOrCreate('admin', 'api');
        $moderatorRole = Role::findOrCreate('moderator', 'api');

        $adminRole->syncPermissions(PermissionName::admin());
        $moderatorRole->syncPermissions(PermissionName::moderator());
    }
}
