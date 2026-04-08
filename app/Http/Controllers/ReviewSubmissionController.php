<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReviewSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
use App\Services\ReviewSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReviewSubmissionController extends Controller
{
    // Aprueba o rechaza submissions con auditoría de revisor y fecha.
    public function __invoke(
        ReviewSubmissionRequest $request,
        Submission $submission,
        ReviewSubmissionService $reviewSubmissionService,
    ): JsonResponse {
        Gate::authorize('review', $submission);

        /** @var User $reviewer */
        $reviewer = $request->user();
        $submission->loadMissing('event');

        abort_unless(
            $this->canReviewEventSubmissions($reviewer, $submission->event),
            403,
            'You are not allowed to review this submission.',
        );

        $reviewedSubmission = $reviewSubmissionService->review($submission, $request->validated(), $reviewer);

        return response()->json([
            'message' => 'Submission reviewed successfully.',
            'data' => new SubmissionResource($reviewedSubmission),
        ]);
    }

    // Restringe revisión a admin o moderador con visibilidad del evento.
    private function canReviewEventSubmissions(User $user, Event $event): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('moderator')) {
            return false;
        }

        if ($event->status === 'published') {
            return true;
        }

        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
