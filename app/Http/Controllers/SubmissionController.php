<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmitFormService;
use Database\Seeders\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubmissionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Submission::class, 'submission', ['except' => ['show', 'store']]);
    }

    // Lista submissions de un evento con control de visibilidad por permisos.
    public function index(Request $request, Event $event): AnonymousResourceCollection|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->canAccessEventSubmissions($user, $event)) {
            return $this->notFoundResponse('Event not found.');
        }

        $submissions = $event->submissions()
            ->with(['members', 'reviewer'])
            ->latest()
            ->paginate();

        return SubmissionResource::collection($submissions)
            ->additional([
                'message' => 'Submissions retrieved successfully.',
                'status' => 200,
            ]);
    }

    // Registra una nueva inscripcion con miembros y respuestas dinamicas.
    public function store(StoreSubmissionRequest $request, Event $event, SubmitFormService $submitFormService): JsonResponse
    {
        $submission = $submitFormService->submit($event, $request->validated());

        return response()->json([
            'message' => 'Submission created successfully.',
            'data' => new SubmissionResource($submission),
            'status' => 201,
        ], 201);
    }

    // Devuelve detalle de una submission si el usuario puede revisarla.
    public function show(Request $request, Submission $submission): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->cannot('view', $submission)) {
            return $this->notFoundResponse('Submission not found.');
        }

        return response()->json([
            'message' => 'Submission retrieved successfully.',
            'data' => new SubmissionResource($submission->load(['event', 'members', 'reviewer'])),
            'status' => 200,
        ], 200);
    }

    private function canAccessEventSubmissions(User $user, Event $event): bool
    {
        if ($user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])) {
            return true;
        }

        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
