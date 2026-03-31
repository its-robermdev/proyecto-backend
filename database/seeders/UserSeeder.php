<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin C3',
            'email' => 'admin@c3.com',
            'password'=> Hash::make('admin123'),
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
