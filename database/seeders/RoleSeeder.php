<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    // Crea roles base usados por authorization en la API.
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::findOrCreate('admin', PermissionCatalog::GUARD_NAME);
        $moderatorRole = Role::findOrCreate('moderator', PermissionCatalog::GUARD_NAME);

        $adminRole->syncPermissions(PermissionCatalog::BY_ROLE['admin']);
        $moderatorRole->syncPermissions(PermissionCatalog::BY_ROLE['moderator']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
