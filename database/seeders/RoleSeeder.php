<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    // Crea roles base usados por authorization en la API.
    public function run(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'moderator']);
    }
}
