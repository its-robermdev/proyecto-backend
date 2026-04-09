<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user', ['except' => ['show', 'update', 'destroy']]);
    }

    // Lista usuarios para administracion interna.
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('roles')
            ->latest()
            ->paginate();

        return UserResource::collection($users)
            ->additional([
                'message' => 'Users retrieved successfully.',
                'status' => 200,
            ]);
    }

    // Crea usuario y asigna roles iniciales opcionales.
    public function store(
        StoreUserRequest $request,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $user = $userManagementService->create($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new UserResource($user->load('roles')),
            'status' => 201,
        ], 201);
    }

    // Muestra detalle de usuario con roles.
    public function show(Request $request, int $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::find($user);

        if (! $targetUser instanceof User) {
            return $this->notFoundResponse('User not found.');
        }

        if ($actor->cannot('view', $targetUser)) {
            return $this->notFoundResponse('User not found.');
        }

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => new UserResource($targetUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Actualiza datos basicos del usuario.
    public function update(
        UpdateUserRequest $request,
        int $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::find($user);

        if (! $targetUser instanceof User) {
            return $this->notFoundResponse('User not found.');
        }

        if ($actor->cannot('update', $targetUser)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $validated = $request->validated();

        $updatedUser = $actor->is($targetUser)
            ? $userManagementService->updateProfile($targetUser, Arr::only($validated, ['name', 'email', 'password']))
            : $userManagementService->updateManagedUser($actor, $targetUser, $validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($updatedUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Elimina logicamente al usuario.
    public function destroy(
        Request $request,
        int $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::withTrashed()->find($user);

        if (! $targetUser instanceof User) {
            return $this->notFoundResponse('User not found.');
        }

        if ($actor->cannot('delete', $targetUser)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        if ($targetUser->trashed()) {
            return $this->conflictResponse('User is already deleted.');
        }

        $targetUser->load('roles');
        $userManagementService->softDelete($actor, $targetUser);

        return response()->json([
            'message' => 'User deleted successfully.',
            'data' => new UserResource($targetUser),
            'status' => 200,
        ], 200);
    }

    // Restaura usuario previamente eliminado (soft delete).
    public function restore(
        Request $request,
        int $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::withTrashed()->find($user);

        if (! $targetUser instanceof User) {
            return $this->notFoundResponse('User not found.');
        }

        if ($actor->cannot('restore', $targetUser)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        if (! $targetUser->trashed()) {
            return $this->conflictResponse('User is already restored.');
        }

        $restoredUser = $userManagementService->restore($actor, $user);

        return response()->json([
            'message' => 'User restored successfully.',
            'data' => new UserResource($restoredUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Activa acceso del usuario en el sistema.
    public function activate(
        Request $request,
        int $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::withTrashed()->find($user);

        if (! $targetUser instanceof User) {
            return $this->notFoundResponse('User not found.');
        }

        if ($actor->cannot('activate', $targetUser)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        if ($targetUser->trashed()) {
            return $this->conflictResponse('Deleted users cannot be activated.');
        }

        if ($targetUser->is_active) {
            return $this->conflictResponse('User is already active.');
        }

        $activatedUser = $userManagementService->activate($actor, $targetUser);

        return response()->json([
            'message' => 'User activated successfully.',
            'data' => new UserResource($activatedUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Desactiva acceso del usuario sin eliminarlo.
    public function deactivate(
        Request $request,
        int $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::withTrashed()->find($user);

        if (! $targetUser instanceof User) {
            return $this->notFoundResponse('User not found.');
        }

        if ($actor->cannot('deactivate', $targetUser)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        if ($targetUser->trashed()) {
            return $this->conflictResponse('Deleted users cannot be deactivated.');
        }

        if (! $targetUser->is_active) {
            return $this->conflictResponse('User is already inactive.');
        }

        $deactivatedUser = $userManagementService->deactivate($actor, $targetUser);

        return response()->json([
            'message' => 'User deactivated successfully.',
            'data' => new UserResource($deactivatedUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Obtiene solo el set de roles del usuario.
    public function roles(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->cannot('roles', $user)) {
            return $this->notFoundResponse('User not found.');
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User roles retrieved successfully.',
            'data' => new UserResource($user),
            'status' => 200,
        ], 200);
    }

    // Sincroniza roles (reemplazo total del set actual).
    public function syncRoles(
        SyncUserRolesRequest $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->cannot('syncRoles', $user)) {
            return $this->forbiddenResponse('You are not allowed to synchronize roles for this user.');
        }

        $updatedUser = $userManagementService->syncRoles(
            $actor,
            $user,
            $request->validated('roles'),
        );

        return response()->json([
            'message' => 'User roles synchronized successfully.',
            'data' => new UserResource($updatedUser->load('roles')),
            'status' => 200,
        ], 200);
    }
}
