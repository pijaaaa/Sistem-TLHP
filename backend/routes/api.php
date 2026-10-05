<?php

use App\Http\Controllers\Api\Access\PermissionController;
use App\Http\Controllers\Api\AuthController;
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

    // Master data
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

    // Account users reuse employee permission matrix
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

    // Access / permission matrix
    Route::middleware(['auth:sanctum', 'permission:access.permissions,view'])->group(function () {
        Route::get('access/permissions', [PermissionController::class, 'index']);
        Route::get('access/permissions/roles/{role}', [PermissionController::class, 'roleMatrix']);
        Route::put('access/permissions/roles/{role}', [PermissionController::class, 'updateRole'])
            ->middleware('permission:access.permissions,update');
        Route::get('access/permissions/users/{user}', [PermissionController::class, 'userMatrix']);
        Route::put('access/permissions/users/{user}', [PermissionController::class, 'updateUserPermission'])
            ->middleware('permission:access.permissions,update');
    });
});
