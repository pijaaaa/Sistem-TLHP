<?php

use App\Http\Controllers\Api\Access\PermissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\ActionPlanController;
use App\Http\Controllers\Api\FindingController;
use App\Http\Controllers\Api\FindingDepartmentController;
use App\Http\Controllers\Api\FindingDocumentController;
use App\Http\Controllers\Api\FindingDistributionController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Master\DepartmentController;
use App\Http\Controllers\Api\Master\EmployeeController;
use App\Http\Controllers\Api\Master\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, '__invoke']);

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    });

    Route::get('/dashboard', function () {
        return \App\Support\ApiResponse::success(['message' => 'Welcome']);
    })->middleware(['auth:sanctum', 'permission:dashboard,view']);

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

    Route::middleware(['auth:sanctum', 'permission:findings.distribution,view'])->group(function () {
        Route::get('findings/distribution', [FindingDistributionController::class, 'pending']);
    });

    Route::middleware(['auth:sanctum', 'permission:findings.reports,view'])->group(function () {
        Route::get('findings', [FindingController::class, 'index']);
        Route::post('findings', [FindingController::class, 'store'])
            ->middleware('permission:findings.reports,create');
        Route::get('findings/{finding}', [FindingController::class, 'show']);
        Route::put('findings/{finding}', [FindingController::class, 'update'])
            ->middleware('permission:findings.reports,update');
        Route::delete('findings/{finding}', [FindingController::class, 'destroy'])
            ->middleware('permission:findings.reports,delete');
        Route::post('findings/{finding}/send-to-ia', [FindingController::class, 'sendToIa'])
            ->middleware('permission:findings.reports,update');
    });

    Route::middleware(['auth:sanctum', 'permission:findings.distribution,view'])->group(function () {
        Route::post('findings/{finding}/distribute', [FindingDistributionController::class, 'distribute'])
            ->middleware('permission:findings.distribution,create');
    });

    Route::middleware(['auth:sanctum', 'permission:findings.reports,view'])->group(function () {
        Route::get('findings/{finding}/documents', [FindingController::class, 'documents']);
        Route::post('findings/{finding}/documents', [FindingController::class, 'uploadDocument'])
            ->middleware('permission:findings.reports,create');
        Route::delete('findings/{finding}/documents/{document}', [FindingController::class, 'deleteDocument'])
            ->middleware('permission:findings.reports,delete');
        Route::get('findings/documents/{document}/download', [FindingDocumentController::class, 'download'])
            ->name('findings.documents.download');
    });

    Route::middleware(['auth:sanctum', 'permission:findings.list,view'])->group(function () {
        Route::get('finding-departments', [FindingDepartmentController::class, 'index']);
        Route::get('finding-departments/{findingDepartment}', [FindingDepartmentController::class, 'show']);
        Route::post('finding-departments/{findingDepartment}/assign-pics', [FindingDepartmentController::class, 'assignPics']);
        Route::post('finding-departments/{findingDepartment}/forward-to-ia', [FindingDepartmentController::class, 'forwardToIa']);
        Route::get('finding-departments/{findingDepartment}/progress', [EvidenceController::class, 'progress']);
    });

    Route::middleware(['auth:sanctum', 'permission:action_plans,view'])->group(function () {
        Route::get('action-plans', [ActionPlanController::class, 'index']);
        Route::post('finding-departments/{findingDepartment}/action-plans', [ActionPlanController::class, 'store'])
            ->middleware('permission:action_plans,create');
        Route::get('action-plans/{actionPlan}', [ActionPlanController::class, 'show']);
        Route::put('action-plans/{actionPlan}', [ActionPlanController::class, 'update'])
            ->middleware('permission:action_plans,update');
        Route::delete('action-plans/{actionPlan}', [ActionPlanController::class, 'destroy'])
            ->middleware('permission:action_plans,delete');
        Route::post('action-plans/{actionPlan}/submit', [ActionPlanController::class, 'submit'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{actionPlan}/approve', [ActionPlanController::class, 'approve'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{actionPlan}/reject', [ActionPlanController::class, 'reject'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{actionPlan}/revision', [ActionPlanController::class, 'requestRevision'])
            ->middleware('permission:action_plans,update');
        Route::post('action-plans/{actionPlan}/override-weight', [ActionPlanController::class, 'overrideWeight'])
            ->middleware('permission:action_plans,update');
        Route::get('action-plans/{actionPlan}/documents', [ActionPlanController::class, 'documents']);
        Route::post('action-plans/{actionPlan}/documents', [ActionPlanController::class, 'uploadDocument'])
            ->middleware('permission:action_plans,create');
        Route::delete('action-plans/{actionPlan}/documents/{document}', [ActionPlanController::class, 'deleteDocument'])
            ->middleware('permission:action_plans,delete');
    });

    Route::get('action-plan-documents/{document}/download', [ActionPlanController::class, 'downloadDocument'])
        ->name('action-plan-documents.download')
        ->middleware(['auth:sanctum']);

    Route::middleware(['auth:sanctum', 'permission:evidence,view'])->group(function () {
        Route::get('action-plans/{actionPlan}/evidence', [EvidenceController::class, 'index']);
        Route::post('action-plans/{actionPlan}/evidence', [EvidenceController::class, 'store'])
            ->middleware('permission:evidence,create');
        Route::post('action-plans/{actionPlan}/evidence/approve', [EvidenceController::class, 'approve'])
            ->middleware('permission:evidence,update');
        Route::post('action-plans/{actionPlan}/evidence/revision', [EvidenceController::class, 'requestRevision'])
            ->middleware('permission:evidence,update');
        Route::get('evidence-files/{file}/download', [EvidenceController::class, 'downloadFile'])
            ->name('evidence-files.download');
    });
});
