<?php

namespace App\Providers;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Finding;
use App\Models\FindingDocument;
use App\Models\Audit;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FindingDocumentPolicy;
use App\Policies\AuditPolicy;
use App\Policies\FindingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        // Batasi percobaan login untuk menahan brute force.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip(),
            );
        });

        // Batas umum untuk endpoint yang menerima unggahan file.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Finding::class, FindingPolicy::class);
        Gate::policy(FindingDocument::class, FindingDocumentPolicy::class);
        Gate::policy(Audit::class, AuditPolicy::class);

        Route::get('/sanctum/csrf-cookie', function () {
            return response()->json(['message' => 'CSRF cookie set']);
        })->name('sanctum.csrf-cookie');
    }
}
