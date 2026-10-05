<?php

namespace App\Http\Controllers\Api;

use App\Enums\FindingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFindingDistributionRequest;
use App\Http\Resources\FindingResource;
use App\Models\Finding;
use App\Services\FindingDepartmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingDistributionController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $findings = Finding::where('status', FindingStatus::SentToIa->value)
            ->withCount('documents')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $data = FindingResource::collection($findings)
            ->toResponse($request)
            ->getData(true);

        return ApiResponse::success($data);
    }

    public function distribute(StoreFindingDistributionRequest $request, Finding $finding): JsonResponse
    {
        FindingDepartmentService::distribute($finding, $request->input('department_ids'));

        return ApiResponse::success(null, 'Temuan berhasil didistribusikan ke departemen.');
    }
}
