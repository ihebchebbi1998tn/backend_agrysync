<?php

/**
 * API routes.
 *
 * All routes here are automatically prefixed with `/api` and respond in
 * JSON. Public endpoints sit at the top; everything that needs a logged
 * in user goes inside the `auth:sanctum` group.
 */

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'service' => config('app.name'),
    'time'    => now()->toIso8601String(),
]));

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me',           [AuthController::class, 'me']);
        Route::post('logout',      [AuthController::class, 'logout']);
        Route::post('logout-all',  [AuthController::class, 'logoutAll']);
    });
});
