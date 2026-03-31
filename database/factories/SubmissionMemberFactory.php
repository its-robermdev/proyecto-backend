<?php

namespace Database\Factories;

use App\Models\SubmissionMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Submission;

/**
 * @extends Factory<SubmissionMember>
 */
class SubmissionMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::inRandomOrder()->first()?->id ?? Submission::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'is_captain' => false,
        ];
    }
}
