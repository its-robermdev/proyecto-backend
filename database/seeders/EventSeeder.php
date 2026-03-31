<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::role('admin')->first();

        $potentialModerators = User::role('moderator')->get();

        Event::factory(20)
            ->create([
                'created_by' => $admin->id ?? User::factory()->create()->assignRole('admin')->id,
            ])
            ->each(function ($event) use ($potentialModerators) {
                if ($potentialModerators->isNotEmpty()) {
                    $moderators = $potentialModerators->random(min($potentialModerators->count(), rand(1, 3)));
                    $event->moderators()->syncWithoutDetaching($moderators->pluck('id'));
                }
            });
    }
}
