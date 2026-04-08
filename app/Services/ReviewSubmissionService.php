<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Submission;
use App\Models\User;

class ReviewSubmissionService
{
    /**
     * @param  array{status: string, review_comment?: string|null}  $validated
     */
    // Persiste decisión de revisión y metadatos de auditoría.
    public function review(Submission $submission, array $validated, User $reviewer): Submission
    {
        $submission->update([
            'status' => $validated['status'],
            'review_comment' => $validated['review_comment'] ?? null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $submission->load(['event', 'members', 'reviewer']);
    }
}
