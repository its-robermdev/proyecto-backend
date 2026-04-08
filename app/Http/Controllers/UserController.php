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
        $this->authorizeResource(User::class, 'user', ['except' => ['show']]);
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
    public function show(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->cannot('view', $user)) {
            return $this->notFoundResponse('User not found.');
        }

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => new UserResource($user->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Actualiza datos basicos del usuario.
    public function update(
        UpdateUserRequest $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $updatedUser = $actor->is($user)
            ? $userManagementService->updateProfile($user, Arr::only($validated, ['name', 'email', 'password']))
            : $userManagementService->updateManagedUser($user, $validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($updatedUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Elimina logicamente al usuario.
    public function destroy(
        Request $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $user->load('roles');
        $userManagementService->softDelete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
            'data' => new UserResource($user),
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
            return $this->forbiddenResponse('You are not allowed to restore this user.');
        }

        $restoredUser = $userManagementService->restore($user);

        return response()->json([
            'message' => 'User restored successfully.',
            'data' => new UserResource($restoredUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Activa acceso del usuario en el sistema.
    public function activate(
        Request $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->cannot('activate', $user)) {
            return $this->forbiddenResponse('You are not allowed to activate this user.');
        }

        $activatedUser = $userManagementService->activate($user);

        return response()->json([
            'message' => 'User activated successfully.',
            'data' => new UserResource($activatedUser->load('roles')),
            'status' => 200,
        ], 200);
    }

    // Desactiva acceso del usuario sin eliminarlo.
    public function deactivate(
        Request $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->cannot('deactivate', $user)) {
            return $this->forbiddenResponse('You are not allowed to deactivate this user.');
        }

        $deactivatedUser = $userManagementService->deactivate($user);

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
