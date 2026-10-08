<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(PermissionService::can(auth()->user(), 'dashboard', 'view'), 403);

        return ApiResponse::success(DashboardService::forUser(auth()->user()));
    }
}