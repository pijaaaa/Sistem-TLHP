<?php

use App\Http\Controllers\Api\Access\PermissionController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FindingController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ActionPlanController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\SpiReviewController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\Master\DepartmentController;
use App\Http\Controllers\Api\Master\EmployeeController;
use App\Http\Controllers\Api\Master\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, '__invoke']);

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        Route::get('/lookups/departments', [LookupController::class, 'departments']);
        Route::get('/lookups/staff', [LookupController::class, 'staff']);
    });

    Route::get('/dashboard', [DashboardController::class, '__invoke'])
        ->middleware(['auth:sanctum', 'permission:dashboard,view']);

    Route::middleware(['auth:sanctum', 'permission:master.departments,view'])->group(function () {
        Route::get('master/departments', [DepartmentController::class, 'index']);
        Route::post('master/departments', [DepartmentController::class, 'store'])
            ->middleware('permission:master.departments,create');
        Route::get('master/departments/{department}', [DepartmentController::class, 'show']);
        Route::put('master/departments/{department}', [DepartmentController::class, 'update'])
            ->middleware('permission:master.departments,update');
        Route::delete('master/departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('permission:master.departments,delete');
    });

    Route::middleware(['auth:sanctum', 'permission:master.employees,view'])->group(function () {
        Route::get('master/employees', [EmployeeController::class, 'index']);
        Route::post('master/employees', [EmployeeController::class, 'store'])
            ->middleware('permission:master.employees,create');
        Route::get('master/employees/{employee}', [EmployeeController::class, 'show']);
        Route::put('master/employees/{employee}', [EmployeeController::class, 'update'])
            ->middleware('permission:master.employees,update');
        Route::delete('master/employees/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:master.employees,delete');
    });

    Route::middleware(['auth:sanctum', 'permission:master.employees,view'])->group(function () {
        Route::get('master/users', [UserController::class, 'index']);
        Route::post('master/users', [UserController::class, 'store'])
            ->middleware('permission:master.employees,create');
        Route::get('master/users/{user}', [UserController::class, 'show']);
        Route::put('master/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:master.employees,update');
        Route::delete('master/users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:master.employees,delete');
    });

    Route::middleware(['auth:sanctum', 'permission:access.permissions,view'])->group(function () {
        Route::get('access/permissions', [PermissionController::class, 'index']);
        Route::get('access/permissions/roles/{role}', [PermissionController::class, 'roleMatrix']);
        Route::put('access/permissions/roles/{role}', [PermissionController::class, 'updateRole'])
            ->middleware('permission:access.permissions,update');
        Route::get('access/permissions/users/{user}', [PermissionController::class, 'userMatrix']);
        Route::put('access/permissions/users/{user}', [PermissionController::class, 'updateUserPermission'])
            ->middleware('permission:access.permissions,update');
    });

    Route::middleware(['auth:sanctum', 'permission:findings,view'])->group(function () {
        Route::get('findings', [FindingController::class, 'index']);
        Route::post('findings', [FindingController::class, 'store'])
            ->middleware('permission:findings,create');
        Route::get('findings/{finding}', [FindingController::class, 'show']);
        Route::put('findings/{finding}', [FindingController::class, 'update'])
            ->middleware('permission:findings,update');
        Route::post('findings/{finding}/register', [FindingController::class, 'register'])
            ->middleware('permission:findings,update');
        Route::post('findings/{finding}/activate', [FindingController::class, 'activate'])
            ->middleware('permission:findings,update');
        Route::delete('findings/{finding}', [FindingController::class, 'destroy'])
            ->middleware('permission:findings,delete');
        Route::get('findings/{finding}/documents', [FindingController::class, 'documents']);
        Route::post('findings/{finding}/documents', [FindingController::class, 'uploadDocument'])
            ->middleware('permission:findings,create');
        Route::delete('findings/{finding}/documents/{document}', [FindingController::class, 'deleteDocument'])
            ->middleware('permission:findings,delete');
        Route::get('findings/{finding}/documents/{document}/download', [FindingController::class, 'downloadDocument'])
            ->name('findings.documents.download');
    });

    Route::middleware(['auth:sanctum', 'permission:action_plans,view'])->group(function () {
        Route::get('action-plans', [ActionPlanController::class, 'index']);
        Route::post('action-plans', [ActionPlanController::class, 'store'])
            ->middleware('permission:action_plans,create');
        Route::post('action-plans/send', [ActionPlanController::class, 'send'])
            ->middleware('permission:action_plans,create');
        Route::get('action-plans/{action_plan}', [ActionPlanController::class, 'show']);
        Route::put('action-plans/{action_plan}', [ActionPlanController::class, 'update'])
            ->middleware('permission:action_plans,update');
        Route::delete('action-plans/{action_plan}', [ActionPlanController::class, 'destroy'])
            ->middleware('permission:action_plans,delete');
        Route::put('action-plans/{action_plan}/deadline', [ActionPlanController::class, 'changeDeadline'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{action_plan}/assign-pics', [ActionPlanController::class, 'assignPics'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{action_plan}/submit-to-spi', [ActionPlanController::class, 'submitToSpi'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{action_plan}/forward-to-pic', [ActionPlanController::class, 'forwardToPic'])
            ->middleware('permission:action_plans,update');
        Route::get('action-plans/{action_plan}/documents', [ActionPlanController::class, 'documents']);
        Route::post('action-plans/{action_plan}/documents', [ActionPlanController::class, 'uploadDocument'])
            ->middleware('permission:action_plans,update');
        Route::delete('action-plans/{action_plan}/documents/{document}', [ActionPlanController::class, 'deleteDocument'])
            ->middleware('permission:action_plans,update');
        Route::get('action-plans/{action_plan}/documents/{document}/download', [ActionPlanController::class, 'downloadDocument'])
            ->name('action-plans.documents.download');
    });

    Route::middleware(['auth:sanctum', 'permission:follow_ups,view'])->group(function () {
        Route::get('follow-ups', [FollowUpController::class, 'index']);
        Route::get('follow-ups/{follow_up}', [FollowUpController::class, 'show']);
        Route::put('follow-ups/{follow_up}', [FollowUpController::class, 'update'])
            ->middleware('permission:follow_ups,update');
        Route::post('follow-ups/submit', [FollowUpController::class, 'submit'])
            ->middleware('permission:follow_ups,update');
        Route::post('action-plans/{action_plan}/follow-ups', [FollowUpController::class, 'store'])
            ->middleware('permission:follow_ups,create');
        Route::get('follow-ups/{follow_up}/reviews', [FollowUpController::class, 'reviews']);
        Route::get('follow-ups/{follow_up}/comments', [FollowUpController::class, 'comments']);
        Route::get('follow-ups/{follow_up}/progress-reports', [FollowUpController::class, 'progressReports']);
        Route::get('follow-ups/{follow_up}/progress-reports/{report}/documents/{document}/download', [FollowUpController::class, 'downloadReportDocument'])
            ->name('follow-ups.progress-reports.documents.download');
        Route::post('follow-ups/{follow_up}/progress', [FollowUpController::class, 'reportProgress'])
            ->middleware('permission:follow_ups,update');
    });

    Route::middleware(['auth:sanctum', 'permission:follow_up_reviews,view'])->group(function () {
        Route::post('follow-ups/{follow_up}/approve', [FollowUpController::class, 'approve'])
            ->middleware('permission:follow_up_reviews,update');
        Route::post('follow-ups/{follow_up}/revision', [FollowUpController::class, 'revision'])
            ->middleware('permission:follow_up_reviews,update');
        Route::post('follow-ups/{follow_up}/reject', [FollowUpController::class, 'reject'])
            ->middleware('permission:follow_up_reviews,update');
        Route::post('follow-ups/{follow_up}/return-to-revision', [FollowUpController::class, 'returnToRevision'])
            ->middleware('permission:follow_up_reviews,update');
        Route::patch('follow-ups/{follow_up}/weight', [FollowUpController::class, 'overrideWeight'])
            ->middleware('permission:follow_up_reviews,update');
        Route::post('follow-ups/{follow_up}/approve-completion', [FollowUpController::class, 'approveCompletion'])
            ->middleware('permission:follow_up_reviews,update');
        Route::post('follow-ups/{follow_up}/completion-revision', [FollowUpController::class, 'completionRevision'])
            ->middleware('permission:follow_up_reviews,update');
    });

    // Komentar lintas peran (PIC, manager, pemantau) diverifikasi di service; label hak akses per FollowUpVisibility.
    Route::post('follow-ups/{follow_up}/comments', [FollowUpController::class, 'addComment'])
        ->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'permission:spi_review,view'])->group(function () {
        Route::get('spi-reviews', [SpiReviewController::class, 'index']);
        Route::get('action-plans/{action_plan}/review-bundle', [SpiReviewController::class, 'bundle']);
        Route::post('action-plans/{action_plan}/spi-review', [SpiReviewController::class, 'assess'])
            ->middleware('permission:spi_review,update');
        Route::post('action-plans/{action_plan}/spi-complete', [SpiReviewController::class, 'complete'])
            ->middleware('permission:spi_review,update');
    });

    Route::middleware(['auth:sanctum', 'permission:audit_trail,view'])->group(function () {
        Route::get('audit-trail', [AuditController::class, 'index']);
        Route::get('audit-trail/actions', [AuditController::class, 'actions']);
    });

    Route::middleware(['auth:sanctum', 'permission:exports,view'])->group(function () {
        Route::get('exports/findings', [ExportController::class, 'findings']);
        Route::get('exports/action-plans', [ExportController::class, 'actionPlans']);
        Route::get('exports/audit-trail', [ExportController::class, 'auditTrail']);
    });
});
