<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        if ($request->is('api/*')) {
            file_put_contents(base_path('storage/logs/debug.log'), "is api: yes | class: " . get_class($e) . " | msg: " . $e->getMessage() . " | isAuth: " . ($e instanceof AuthenticationException ? '1' : '0') . " | isHttp: " . ($e instanceof HttpException ? '1' : '0') . " | path: " . $request->path() . " | exp: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            if ($e instanceof AuthenticationException) {
                return \App\Support\ApiResponse::error('Unauthenticated.', 401);
            }

            if ($e instanceof ValidationException) {
                return \App\Support\ApiResponse::validationError($e->errors(), $e->getMessage());
            }

            if ($e instanceof HttpException) {
                return \App\Support\ApiResponse::error($e->getMessage(), $e->getStatusCode());
            }

            return \App\Support\ApiResponse::error($e->getMessage(), 500);
        }

        return parent::render($request, $e);
    }

    protected function shouldReturnJson($request, $e): bool
    {
        return $request->is('api/*');
    }
}