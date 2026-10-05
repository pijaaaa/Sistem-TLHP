<?php

namespace App\Providers;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Models\FindingDocument;
use App\Models\ActionPlan;
use App\Models\ActionPlanDocument;
use App\Models\EvidenceFile;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FindingDocumentPolicy;
use App\Policies\FindingDepartmentPolicy;
use App\Policies\ActionPlanPolicy;
use App\Policies\ActionPlanDocumentPolicy;
use App\Policies\EvidenceFilePolicy;
use App\Policies\FindingPolicy;
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
        Gate::policy(Finding::class, FindingPolicy::class);
        Gate::policy(FindingDocument::class, FindingDocumentPolicy::class);
        Gate::policy(FindingDepartment::class, FindingDepartmentPolicy::class);
        Gate::policy(ActionPlan::class, ActionPlanPolicy::class);
        Gate::policy(ActionPlanDocument::class, ActionPlanDocumentPolicy::class);
        Gate::policy(EvidenceFile::class, EvidenceFilePolicy::class);

        Route::get('/sanctum/csrf-cookie', function () {
            return response()->json(['message' => 'CSRF cookie set']);
        })->name('sanctum.csrf-cookie');
    }
}
