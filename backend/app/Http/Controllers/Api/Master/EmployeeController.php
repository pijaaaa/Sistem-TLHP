<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Policies\EmployeePolicy;
use App\Services\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        return ApiResponse::success(
            EmployeeResource::collection(EmployeeService::paginated($perPage))
        );
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = EmployeeService::create($request->validated());

        return ApiResponse::success(new EmployeeResource($employee), 'Karyawan berhasil ditambahkan.', 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        return ApiResponse::success(new EmployeeResource($employee->load('department')));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee = EmployeeService::update($employee, $request->validated());

        return ApiResponse::success(new EmployeeResource($employee), 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): JsonResponse
    {
        EmployeeService::delete($employee);

        return ApiResponse::success(null, 'Karyawan berhasil dihapus.');
    }
}
