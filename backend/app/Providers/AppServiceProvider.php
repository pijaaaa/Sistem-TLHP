<?php

namespace App\Providers;

use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::aliasMiddleware('permission', PermissionMiddleware::class);

        // Pastikan sanctum routes untuk API sudah terdaftar
        Route::get('/sanctum/csrf-cookie', function () {
            return response()->json(['message' => 'CSRF cookie set']);
        })->name('sanctum.csrf-cookie');
    }
}
