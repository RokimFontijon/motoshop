<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetDatabaseSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short names used in routes/web.php:
        //   role:admin   -> only admins may enter
        //   db.session   -> tells PostgreSQL who is logged in (for row level security)
        $middleware->alias([
            'role' => EnsureRole::class,
            'db.session' => SetDatabaseSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
