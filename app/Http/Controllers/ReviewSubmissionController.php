<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Models\User;
use App\Services\ReviewSubmissionService;
use Illuminate\Http\JsonResponse;

class ReviewSubmissionController extends Controller
{
    // Aprueba o rechaza submissions con auditoria de revisor y fecha.
    public function __invoke(
        ReviewSubmissionRequest $request,
        Submission $submission,
        ReviewSubmissionService $reviewSubmissionService,
    ): JsonResponse {
        /** @var User $reviewer */
        $reviewer = $request->user();

        if ($reviewer->cannot('review', $submission)) {
            return $this->forbiddenResponse('You are not allowed to review this submission.');
        }

        $reviewedSubmission = $reviewSubmissionService->review($submission, $request->validated(), $reviewer);

        return response()->json([
            'message' => 'Submission reviewed successfully.',
            'data' => new SubmissionResource($reviewedSubmission),
            'status' => 200,
        ], 200);
    }
}
