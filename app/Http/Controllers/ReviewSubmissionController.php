<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReviewSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use App\Models\User;
use App\Services\ReviewSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReviewSubmissionController extends Controller
{
    public function __invoke(
        ReviewSubmissionRequest $request,
        Submission $submission,
        ReviewSubmissionService $reviewSubmissionService,
    ): JsonResponse {
        Gate::authorize('review', $submission);

        /** @var User $reviewer */
        $reviewer = $request->user();

        $reviewedSubmission = $reviewSubmissionService->review($submission, $request->validated(), $reviewer);

        return response()->json([
            'message' => 'Submission reviewed successfully.',
            'data' => new SubmissionResource($reviewedSubmission),
        ]);
    }
}
