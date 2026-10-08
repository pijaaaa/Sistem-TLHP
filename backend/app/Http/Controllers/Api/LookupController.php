<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Support\ApiResponse;
use App\Support\CacheService;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function departments(Request $request)
    {
        $auditee = $request->query('auditee');
        $cacheKey = 'departments_' . ($auditee ? 'auditee' : 'all');

        $departments = CacheService::remember('lookups', $cacheKey, function () use ($auditee) {
            $query = Department::where('is_active', true);

            if ($auditee) {
                $query->where('is_auditee', true);
            }

            return $query->orderBy('name')->get();
        });

        return ApiResponse::success([
            'data' => DepartmentResource::collection($departments),
        ]);
    }
}

