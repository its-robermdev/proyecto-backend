<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Orden de seeders pensado para respetar dependencias entre tablas.
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            EventSeeder::class,
            SubmissionSeeder::class,
            SubmissionMemberSeeder::class,
        ]);
    }
}
