<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssessmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssessFindingRequest;
use App\Http\Resources\FindingResource;
use App\Models\Finding;
use App\Services\AssessmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Finding::class);

        $perPage = (int) $request->query('per_page', 15);

        $findings = Finding::where('status', \App\Enums\FindingStatus::PendingIaAssessment->value)
            ->visible()
            ->withCount('documents')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        \App\Services\ActionPlanService::attachFindingProgress($findings->items());

        $data = FindingResource::collection($findings)
            ->toResponse($request)
            ->getData(true);

        return ApiResponse::success($data);
    }

    public function store(AssessFindingRequest $request, Finding $finding): JsonResponse
    {
        $this->authorize('assess', $finding);

        $assessment = AssessmentStatus::from($request->validated('assessment_status'));

        $updated = AssessmentService::assess(
            $finding,
            $assessment,
            $request->validated('note'),
            $request->validated('department_ids'),
        );

        return ApiResponse::success(new FindingResource($updated), 'Assessment berhasil disimpan.');
    }
}