<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Finding::class);

        return ApiResponse::success(DashboardService::forUser(auth()->user()));
    }
}