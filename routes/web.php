<?php

/**
 * Web routes.
 *
 * This service is API-only, so the web router only exposes a tiny root
 * endpoint that confirms the app is reachable from a browser. Everything
 * useful lives under /api.
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name'   => config('app.name'),
    'status' => 'ok',
]));
