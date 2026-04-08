<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    // Siembra usuarios de referencia (admin/mod) y lotes de soporte para pruebas.
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin C3',
            'email' => 'admin@c3.com',
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('admin');

        $mod = User::factory()->create([
            'name' => 'Mod C3',
            'email' => 'mod@c3.com',
            'password' => Hash::make('mod123'),
        ]);
        $mod->assignRole('moderator');

        User::factory(10)->create()->each(function (User $user) {
            $user->assignRole('moderator');
        });

        User::factory(5)->create()->each(function (User $user) {
            $user->assignRole('admin');
        });

        User::factory(2)->inactive()->create()->each(function (User $user) {
            $user->assignRole('moderator');
        });
    }
}
