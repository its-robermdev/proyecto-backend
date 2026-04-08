<?php

namespace Database\Seeders;

use App\Models\Submission;
use App\Models\SubmissionMember;
use Illuminate\Database\Seeder;

class SubmissionMemberSeeder extends Seeder
{
    // Para teams, crea capitán y miembros extra vinculados a la submission.
    public function run(): void
    {
        $teamSubmissions = Submission::where('participation_type', 'team')->get();

        foreach ($teamSubmissions as $submission) {
            SubmissionMember::factory()->create([
                'submission_id' => $submission->id,
                'full_name' => $submission->submitted_by_name,
                'email' => $submission->submitted_by_email,
                'is_captain' => true,
            ]);

            $extraMembers = rand(1, 3);
            SubmissionMember::factory($extraMembers)->create([
                'submission_id' => $submission->id,
                'is_captain' => false,
            ]);
        }
    }
}
