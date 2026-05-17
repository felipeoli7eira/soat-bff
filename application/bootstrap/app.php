<?php

use App\Http\Middleware\JsonWebTokenMiddleware;
use App\Http\Middleware\OpenTelemetryMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(OpenTelemetryMiddleware::class);
        $middleware->api([]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
