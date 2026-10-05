<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware()
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return \App\Support\ApiResponse::error($e->getMessage(), 401);
                }
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return \App\Support\ApiResponse::error($e->getMessage(), 422, $e->errors());
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    return \App\Support\ApiResponse::error($e->getMessage(), $e->getStatusCode());
                }
                return \App\Support\ApiResponse::error($e->getMessage(), 500);
            }
        });
    })->create();
