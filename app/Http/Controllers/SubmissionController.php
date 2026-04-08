<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmitFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SubmissionController extends Controller
{
    // Lista submissions de un evento con control de visibilidad por rol.
    public function index(Request $request, Event $event): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Submission::class);
        $user = $request->user();

        abort_unless($user instanceof User && $this->canViewEventSubmissions($user, $event), 403, 'You are not allowed to inspect submissions for this event.');

        $submissions = $event->submissions()
            ->with(['members', 'reviewer'])
            ->latest()
            ->paginate();

        return SubmissionResource::collection($submissions)
            ->additional(['message' => 'Submissions retrieved successfully.']);
    }

    // Registra una nueva inscripción con miembros y respuestas dinámicas.
    public function store(StoreSubmissionRequest $request, Event $event, SubmitFormService $submitFormService): JsonResponse
    {
        $submission = $submitFormService->submit($event, $request->validated());

        return response()->json([
            'message' => 'Submission created successfully.',
            'data' => new SubmissionResource($submission),
        ], 201);
    }

    // Devuelve detalle de una submission si el usuario puede revisarla.
    public function show(Request $request, Submission $submission): JsonResponse
    {
        Gate::authorize('view', $submission);
        $user = $request->user();

        $submission->loadMissing('event');
        abort_unless(
            $user instanceof User && $this->canViewEventSubmissions($user, $submission->event),
            403,
            'You are not allowed to inspect this submission.',
        );

        return response()->json([
            'message' => 'Submission retrieved successfully.',
            'data' => new SubmissionResource($submission->load(['event', 'members', 'reviewer'])),
        ]);
    }

    // Reusa regla de acceso: admin o moderador autorizado en el evento.
    private function canViewEventSubmissions(User $user, Event $event): bool
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
