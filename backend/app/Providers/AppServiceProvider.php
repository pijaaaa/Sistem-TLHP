<?php

namespace App\Providers;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Route::aliasMiddleware('permission', PermissionMiddleware::class);

        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Route::get('/sanctum/csrf-cookie', function () {
            return response()->json(['message' => 'CSRF cookie set']);
        })->name('sanctum.csrf-cookie');
    }
}
