<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        \Log::info('HandleCors: Origin=' . $origin . ', Method=' . $request->method());

        if ($this->isAllowedOrigin($origin)) {
            if ($request->isMethod('OPTIONS')) {
                return response()->json([], 204)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN')
                    ->header('Access-Control-Expose-Headers', 'Content-Disposition');
            }

            $response = $next($request);
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
            return $response;
        }

        return $next($request);
    }

    protected function isAllowedOrigin($origin)
    {
        $allowed = [
            'http://localhost:5173',
            'http://localhost:3000',
            env('APP_URL'),
            env('FRONTEND_URL'),
        ];

        return in_array($origin, array_filter($allowed));
    }
}
