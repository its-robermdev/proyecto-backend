<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Event;
use App\Models\Submission;
use App\Services\SubmitFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SubmissionController extends Controller
{
    public function index(Event $event): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Submission::class);

        $submissions = $event->submissions()
            ->with(['members', 'reviewer'])
            ->latest()
            ->paginate();

        return SubmissionResource::collection($submissions);
    }

    public function store(StoreSubmissionRequest $request, Event $event, SubmitFormService $submitFormService): JsonResponse
    {
        $submission = $submitFormService->submit($event, $request->validated());

        return response()->json([
            'message' => 'Submission created successfully.',
            'data' => new SubmissionResource($submission),
        ], 201);
    }

    public function show(Submission $submission): SubmissionResource
    {
        Gate::authorize('view', $submission);

        return new SubmissionResource($submission->load(['event', 'members', 'reviewer']));
    }
}
