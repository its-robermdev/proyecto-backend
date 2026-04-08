<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    // Punto base para controladores HTTP de la API.
    protected function forbiddenResponse(string $message = 'This action is unauthorized.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => null,
            'status' => 403,
        ], 403);
    }

    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => null,
            'status' => 404,
        ], 404);
    }
}
