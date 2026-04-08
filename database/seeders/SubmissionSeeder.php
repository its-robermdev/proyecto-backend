<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Submission;
use Illuminate\Database\Seeder;

class SubmissionSeeder extends Seeder
{
    // Siembra submissions por evento y completa respuestas según schema dinámico.
    public function run(): void
    {
        $events = Event::with('moderators')->get();

        if ($events->isEmpty()) {
            $this->command->warn('No hay eventos. Ejecuta EventSeeder primero.');

            return;
        }

        foreach ($events as $event) {
            $numSubmissions = rand(8, 20);

            for ($i = 0; $i < $numSubmissions; $i++) {
                $submission = Submission::factory()->create([
                    'event_id' => $event->id,
                ]);

                $updates = [];

                if (is_array($event->form_schema)) {
                    $updates['form_answers'] = $this->generateAnswersForEvent($event->form_schema);
                }

                if (in_array($submission->status, ['approved', 'rejected']) && $event->moderators->isNotEmpty()) {
                    $moderator = $event->moderators->random();
                    $updates['reviewed_by'] = $moderator->id;
                    $updates['reviewed_at'] = now()->subDays(rand(1, 5));
                }

                if (! empty($updates)) {
                    $submission->update($updates);
                }
            }
        }
    }

    // Genera respuestas fake coherentes con los tipos de campo del schema.
    private function generateAnswersForEvent(array $schema): array
    {
        $answers = [];

        foreach ($schema as $step) {
            if (! isset($step['fields'])) {
                continue;
            }

            foreach ($step['fields'] as $field) {
                $name = $field['name'];

                $answers[$name] = match ($field['type'] ?? 'text') {
                    'text' => fake()->sentence(3),
                    'textarea' => fake()->paragraph(),
                    'number' => fake()->numberBetween(2020, 2030),
                    'url' => fake()->url(),
                    'select' => fake()->randomElement($field['options'] ?? ['Opción A', 'Opción B']),
                    default => fake()->word(),
                };
            }
        }

        return $answers;
    }
}
