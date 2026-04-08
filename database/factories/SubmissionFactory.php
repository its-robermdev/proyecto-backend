<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    // Genera submissions con combinaciones de tipo y estado.
    public function definition(): array
    {
        $type = fake()->randomElement(['individual', 'team']);
        $status = fake()->randomElement([
            Submission::STATUS_PENDING,
            Submission::STATUS_APPROVED,
            Submission::STATUS_REJECTED,
        ]);

        return [
            'event_id' => Event::inRandomOrder()->first()?->id ?? Event::factory(),
            'submitted_by_name' => fake()->name(),
            'submitted_by_email' => fake()->safeEmail(),
            'participation_type' => $type,
            'team_name' => $type === 'team' ? fake()->company().' Team' : null,
            'status' => $status,
            'review_comment' => $status === Submission::STATUS_REJECTED ? fake()->sentence() : null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'form_answers' => [],
        ];
    }
}
