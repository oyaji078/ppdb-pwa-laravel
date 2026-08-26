<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetNoStoreHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Keeps the signed-in password hash in the session, so changing a
            // password (an applicant reset, a staff password change) signs that
            // account out everywhere else.
            AuthenticateSession::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
            'no-store' => SetNoStoreHeaders::class,
        ]);

        // Everyone — applicant or staff — signs in at the same place.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Someone already signed in who asks for the sign-in page is sent to
        // their own home rather than Laravel's "/dashboard" default, which does
        // not exist here. Without this an applicant who is still signed in is
        // silently bounced off the login form and cannot tell why.
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->homeUrl() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
