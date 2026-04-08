<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReviewSubmissionController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/v1/auth/login', [AuthController::class, 'login']);

Route::post('/events/{event}/submissions', [SubmissionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/events/{event}/submissions', [SubmissionController::class, 'index']);
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
    Route::patch('/submissions/{submission}/review', ReviewSubmissionController::class);
});
