<?php

namespace App\Exceptions;

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
            if ($e instanceof ValidationException) {
                return \App\Support\ApiResponse::validationError($e->errors(), $e->getMessage());
            }

            if ($e instanceof NotFoundHttpException) {
                return \App\Support\ApiResponse::notFound($e->getMessage());
            }

            if ($e instanceof HttpException) {
                return \App\Support\ApiResponse::error($e->getMessage(), $e->getStatusCode());
            }

            if (get_class($e) === \Illuminate\Auth\AuthenticationException::class) {
                return \App\Support\ApiResponse::error('Unauthenticated', 401);
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
