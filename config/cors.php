<?php

/**
 * CORS config.
 *
 * Reads the comma-separated `CORS_ALLOWED_ORIGINS` env var so each
 * environment can whitelist its own front-end without code changes.
 * Credentials are enabled to keep the door open for cookie-based auth.
 */

declare(strict_types=1);

$origins = array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:8080'))
));

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => $origins ?: ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
