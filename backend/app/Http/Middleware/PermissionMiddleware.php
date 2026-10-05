<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $menuCode, string $action = 'view'): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!PermissionService::can($user, $menuCode, $action)) {
            return response()->json(['message' => 'Forbidden: insufficient permissions.'], 403);
        }

        return $next($request);
    }
}