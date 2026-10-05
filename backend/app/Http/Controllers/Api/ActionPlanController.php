<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActionPlanRequest;
use App\Http\Requests\UpdateActionPlanRequest;
use App\Http\Requests\UploadActionPlanDocumentRequest;
use App\Http\Resources\ActionPlanDocumentResource;
use App\Http\Resources\ActionPlanResource;
use App\Models\ActionPlan;
use App\Models\ActionPlanDocument;
use App\Models\FindingDepartment;
use App\Services\ActionPlanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActionPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActionPlan::class);

        $perPage = (int) $request->query('per_page', 15);

        $paginator = ActionPlanService::paginated($perPage);
        $data = ActionPlanResource::collection($paginator)
            ->toResponse($request)
            ->getData(true);

        return ApiResponse::success($data);
    }

    public function store(StoreActionPlanRequest $request, FindingDepartment $findingDepartment): JsonResponse
    {
        $this->authorize('create', ActionPlan::class);

        $ap = ActionPlanService::create($findingDepartment, $request->validated());

        return ApiResponse::success(new ActionPlanResource($ap), 'Rencana aksi berhasil ditambahkan.', 201);
    }

    public function show(ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('view', $actionPlan);
        $actionPlan->load(['findingDepartment.finding', 'findingDepartment.department', 'creator', 'approver']);

        return ApiResponse::success(new ActionPlanResource($actionPlan));
    }

    public function update(UpdateActionPlanRequest $request, ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('update', $actionPlan);

        $ap = ActionPlanService::update($actionPlan, $request->validated());

        return ApiResponse::success(new ActionPlanResource($ap), 'Rencana aksi berhasil diperbarui.');
    }

    public function destroy(ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('delete', $actionPlan);

        ActionPlanService::delete($actionPlan);

        return ApiResponse::success(null, 'Rencana aksi berhasil dihapus.');
    }

    public function submit(ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('submit', $actionPlan);

        $ap = ActionPlanService::submit($actionPlan);

        return ApiResponse::success(new ActionPlanResource($ap), 'Rencana aksi berhasil diajukan.');
    }

    public function documents(ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('view', $actionPlan);

        return ApiResponse::success(ActionPlanDocumentResource::collection($actionPlan->documents));
    }

    public function uploadDocument(UploadActionPlanDocumentRequest $request, ActionPlan $actionPlan): JsonResponse
    {
        $this->authorize('uploadDocument', $actionPlan);

        $document = ActionPlanService::uploadDocument($actionPlan, $request->file('document'), $request->string('label'));

        return ApiResponse::success(new ActionPlanDocumentResource($document), 'Dokumen berhasil diunggah.', 201);
    }

    public function deleteDocument(ActionPlan $actionPlan, ActionPlanDocument $document): JsonResponse
    {
        $this->authorize('uploadDocument', $actionPlan);

        abort_if($document->action_plan_id !== $actionPlan->id, 404);

        ActionPlanService::deleteDocument($document);

        return ApiResponse::success(null, 'Dokumen berhasil dihapus.');
    }

    public function downloadDocument(ActionPlanDocument $document)
    {
        return response()->file(
            Storage::disk(config('upload.disk'))->path($document->path),
            ['Content-Type' => $document->mime, 'Content-Disposition' => 'attachment; filename="' . $document->name . '"']
        );
    }
}
