<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignPicsRequest;
use App\Http\Resources\FindingDepartmentResource;
use App\Models\FindingDepartment;
use App\Services\FindingDepartmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingDepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FindingDepartment::class);

        $perPage = (int) $request->query('per_page', 15);

        $paginator = FindingDepartment::query()
            ->visible()
            ->with(['finding', 'department'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $data = FindingDepartmentResource::collection($paginator)
            ->toResponse($request)
            ->getData(true);

        return ApiResponse::success($data);
    }

    public function show(Request $request, FindingDepartment $findingDepartment): JsonResponse
    {
        $this->authorize('view', $findingDepartment);
        $findingDepartment->load(['finding', 'department', 'pics']);

        return ApiResponse::success(new FindingDepartmentResource($findingDepartment));
    }

    public function assignPics(AssignPicsRequest $request, FindingDepartment $findingDepartment): JsonResponse
    {
        $this->authorize('assignPics', $findingDepartment);

        FindingDepartmentService::assignPics($findingDepartment, $request->validated('pic_ids'));

        return ApiResponse::success(null, 'PIC berhasil ditugaskan.');
    }

    public function forwardToIa(FindingDepartment $findingDepartment): JsonResponse
    {
        $this->authorize('forwardToIa', $findingDepartment);

        $fd = FindingDepartmentService::forwardToIA($findingDepartment);

        return ApiResponse::success(new FindingDepartmentResource($fd), 'Temuan berhasil diteruskan ke IA.');
    }
}
