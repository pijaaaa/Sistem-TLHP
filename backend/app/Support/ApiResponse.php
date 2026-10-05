<?php

namespace App\Support;

class ApiResponse
{
    public static function success($data = null, ?string $message = null, int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    public static function error(string $message, int $code = 400, ?array $errors = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function notFound(string $message = 'Resource not found'): \Illuminate\Http\JsonResponse
    {
        return self::error($message, 404);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): \Illuminate\Http\JsonResponse
    {
        return self::error($message, 422, $errors);
    }
}
