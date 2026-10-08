<?php

namespace App\Providers;

use App\Http\Middleware\PermissionMiddleware;
use App\Listeners\DispatchNotificationTasks;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Models\Audit;
use App\Models\User;
use App\Policies\ActionPlanPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FollowUpPolicy;
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
        Gate::policy(ActionPlan::class, ActionPlanPolicy::class);
        Gate::policy(FollowUp::class, FollowUpPolicy::class);
        Gate::policy(Audit::class, AuditPolicy::class);

        \Illuminate\Support\Facades\Event::listen([
            \App\Events\ActionPlanSent::class,
            \App\Events\PicAssigned::class,
            \App\Events\FollowUpSubmitted::class,
            \App\Events\FollowUpDecided::class,
            \App\Events\FollowUpReturnedToRevision::class,
            \App\Events\IaCommentAdded::class,
            \App\Events\ProgressReported::class,
            \App\Events\CompletionRequested::class,
            \App\Events\CompletionDecided::class,
            \App\Events\ActionPlanSubmittedToSpi::class,
            \App\Events\SpiReviewCompleted::class,
            \App\Events\RevisionForwarded::class,
            \App\Events\FindingWaitingExternal::class,
            \App\Events\ExternalStatusRecorded::class,
            \App\Events\FindingClosed::class,
        ], DispatchNotificationTasks::class);

        Route::get('/sanctum/csrf-cookie', function () {
            return response()->json(['message' => 'CSRF cookie set']);
        })->name('sanctum.csrf-cookie');
    }
}
