<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFormController;
use App\Http\Controllers\EventModeratorController;
use App\Http\Controllers\EventStatusController;
use App\Http\Controllers\ReviewSubmissionController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Agrupar todo en v1
Route::prefix('/v1')->group(function (): void {
    // Endpoints de sesión y perfil.
    Route::prefix('/auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/profile', [AuthController::class, 'profile']);
        });
    });

    // Lectura pública de eventos y formulario activo; inscripción pública.
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/events/{event}/form', [EventFormController::class, 'show']);
    Route::post('/events/{event}/submissions', [SubmissionController::class, 'store']);

    // Rutas que requieren autenticación para gestión administrativa, moderación y revisión.
    Route::middleware('auth:sanctum')->group(function (): void {
        // Gestión administrativa de eventos.
        Route::post('/events', [EventController::class, 'store']);
        Route::patch('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::put('/events/{event}/restore', [EventController::class, 'restore']);
        Route::put('/events/{event}/status', EventStatusController::class);

        // Configuración del formulario por evento.
        Route::put('/events/{event}/form', [EventFormController::class, 'update']);
        Route::post('/events/{event}/form/validation', [EventFormController::class, 'validateSchema']);
        Route::put('/events/{event}/form/activation', [EventFormController::class, 'activate']);
        Route::delete('/events/{event}/form/activation', [EventFormController::class, 'deactivate']);

        // Gestión de responsables/moderadores por evento.
        Route::get('/events/{event}/moderators', [EventModeratorController::class, 'index']);
        Route::post('/events/{event}/moderators', [EventModeratorController::class, 'store']);
        Route::delete('/events/{event}/moderators/{user}', [EventModeratorController::class, 'destroy']);

        // Backoffice de revisión y consulta de submissions.
        Route::get('/events/{event}/submissions', [SubmissionController::class, 'index']);
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
        Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy']);
        Route::put('/submissions/{submission}/restore', [SubmissionController::class, 'restore']);
        Route::patch('/submissions/{submission}/review', ReviewSubmissionController::class);

        // Administración de usuarios y roles.
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::put('/users/{user}/restore', [UserController::class, 'restore']);
        Route::put('/users/{user}/activation', [UserController::class, 'activate']);
        Route::delete('/users/{user}/activation', [UserController::class, 'deactivate']);
        Route::get('/users/{user}/roles', [UserController::class, 'roles']);
        Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
    });
});
