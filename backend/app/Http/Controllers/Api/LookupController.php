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

    public function staff(Request $request)
    {
        $departmentId = (int) $request->query('department_id');
        abort_if($departmentId === 0, 422, 'department_id wajib diisi.');

        $staff = \App\Models\User::where('department_id', $departmentId)
            ->where('role', \App\Enums\Role::StaffDept->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return ApiResponse::success(['data' => $staff]);
    }
}

