<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUntil
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role !== Role::SuperAdmin->value) {
            if ($user->active_until && \Carbon\Carbon::parse($user->active_until)->startOfDay() < now()->startOfDay()) {
                return response()->json([
                    'message' => 'Akun Anda telah kadaluarsa dan tidak dapat diakses.',
                ], 401);
            }
        }

        return $next($request);
    }
}
