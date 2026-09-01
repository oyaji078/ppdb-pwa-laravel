<?php

/*
|--------------------------------------------------------------------------
| Vercel entry point
|--------------------------------------------------------------------------
|
| Every request on Vercel is rewritten to this file. The invocation gets a
| read-only filesystem with /tmp as the only writable location, so the paths
| Laravel writes to are redirected there before the framework boots.
|
| public/index.php is left alone: it still serves `php artisan serve` and any
| ordinary Apache or Nginx host.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// php-cgi does not always publish the process environment into $_ENV or
// $_SERVER, and Laravel's env repository reads only those two. Without this
// copy the variables configured on the Vercel project are invisible to
// config(), and the app boots against its defaults instead.
foreach (getenv() as $key => $value) {
    $_ENV[$key] ??= $value;
    $_SERVER[$key] ??= $value;
}

$storage = '/tmp/storage';

// Laravel assumes these exist. Nothing survives between invocations, which is
// why uploads live on Vercel Blob and sessions and the cache live in the
// database; what lands here is only ever scratch space.
foreach ([
    '/tmp/bootstrap/cache',
    $storage.'/app/private',
    $storage.'/app/public',
    $storage.'/framework/cache/data',
    $storage.'/framework/sessions',
    $storage.'/framework/views',
    $storage.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }
}

// Set here rather than in vercel.json, whose env block also applies during the
// build: pointing the package manifest at a /tmp path that does not exist yet
// would make composer's package:discover fail before the app is ever deployed.
foreach ([
    'LARAVEL_STORAGE_PATH' => $storage,
    'VIEW_COMPILED_PATH' => $storage.'/framework/views',
    'APP_CONFIG_CACHE' => '/tmp/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => '/tmp/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/bootstrap/cache/routes-v7.php',
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/cache/services.php',
] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
}

// Neon picks the database branch from the TLS server name, which the libpq
// built into this runtime is too old to send: every connection comes back as
// "Endpoint ID is not specified". libpq does read PGOPTIONS when it connects,
// so the endpoint is taken from the host it would otherwise have signalled and
// passed along explicitly. Derived rather than hardcoded so that pointing the
// app at another branch stays a matter of changing the URL alone.
if (getenv('PGOPTIONS') === false) {
    $postgresHost = parse_url((string) ($_ENV['DB_URL'] ?? $_ENV['POSTGRES_URL'] ?? ''), PHP_URL_HOST);

    if (is_string($postgresHost) && str_ends_with($postgresHost, '.neon.tech')) {
        putenv('PGOPTIONS=endpoint='.str_replace('-pooler', '', strtok($postgresHost, '.')));
    }
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
