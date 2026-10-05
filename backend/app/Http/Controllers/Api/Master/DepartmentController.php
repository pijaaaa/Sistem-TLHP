<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Policies\DepartmentPolicy;
use App\Services\DepartmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        return ApiResponse::success(
            DepartmentResource::collection(DepartmentService::paginated($perPage))
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = DepartmentService::create($request->validated());

        return ApiResponse::success(new DepartmentResource($department), 'Departemen berhasil ditambahkan.', 201);
    }

    public function show(Department $department): JsonResponse
    {
        return ApiResponse::success(new DepartmentResource($department));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department = DepartmentService::update($department, $request->validated());

        return ApiResponse::success(new DepartmentResource($department), 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department): JsonResponse
    {
        DepartmentService::delete($department);

        return ApiResponse::success(null, 'Departemen berhasil dihapus.');
    }
}
