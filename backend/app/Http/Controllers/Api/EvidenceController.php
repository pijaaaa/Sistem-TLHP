<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitEvidenceRequest;
use App\Http\Resources\ActionPlanResource;
use App\Http\Resources\EvidenceSubmissionResource;
use App\Models\ActionPlan;
use App\Models\EvidenceFile;
use App\Models\FindingDepartment;
use App\Services\ActionPlanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function index(Request $request, ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('view', $actionPlan);

        $submissions = $actionPlan->evidenceSubmissions()->with('files')->latest('id')->get();

        return ApiResponse::success(EvidenceSubmissionResource::collection($submissions));
    }

    public function store(SubmitEvidenceRequest $request, ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('submitEvidence', $actionPlan);

        $submission = ActionPlanService::submitEvidence($actionPlan, $request->validated('files'));
        $submission->load('files');

        return ApiResponse::success(
            new EvidenceSubmissionResource($submission),
            'Evidence berhasil diajukan.',
            201,
        );
    }

    public function approve(ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('reviewEvidence', $actionPlan);

        $ap = ActionPlanService::approveEvidence($actionPlan);

        return ApiResponse::success(new ActionPlanResource($ap), 'Evidence berhasil disetujui.');
    }

    public function requestRevision(Request $request, ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('reviewEvidence', $actionPlan);

        $note = (string) $request->input('note', '');
        $ap = ActionPlanService::requestEvidenceRevision($actionPlan, $note);

        return ApiResponse::success(new ActionPlanResource($ap), 'Permintaan revisi evidence dikirim.');
    }

    public function downloadFile(EvidenceFile $file)
    {
        $this->authorize('view', $file->submission->actionPlan);

        return Storage::disk(config('upload.disk'))->download($file->path, $file->name);
    }

    public function progress(FindingDepartment $findingDepartment): JsonResponse
    {
        $this->authorize('view', $findingDepartment);

        return ApiResponse::success([
            'finding_department_id' => $findingDepartment->id,
            'progress' => ActionPlanService::departmentProgress($findingDepartment->id),
            'status' => $findingDepartment->status->value,
            'status_label' => $findingDepartment->status->label(),
        ]);
    }
}