<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            Route::get('/health', HealthController::class);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => RequireRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
