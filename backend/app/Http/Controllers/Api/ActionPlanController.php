<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignPicsRequest;
use App\Http\Requests\SendActionPlanRequest;
use App\Http\Requests\StoreActionPlanRequest;
use App\Http\Requests\UpdateActionPlanDeadlineRequest;
use App\Http\Requests\UpdateActionPlanRequest;
use App\Http\Requests\UploadActionPlanDocumentRequest;
use App\Http\Resources\ActionPlanResource;
use App\Http\Resources\DocumentResource;
use App\Models\ActionPlan;
use App\Models\Document;
use App\Models\Finding;
use App\Services\ActionPlanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActionPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActionPlan::class);

        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = ActionPlan::query()->with(['finding', 'department', 'assignees'])
            ->orderBy('id', 'desc');

        if ($findingId = $request->query('finding_id')) {
            $query->where('finding_id', (int) $findingId);
        }

        if ($deptId = $request->query('department_id')) {
            $query->where('department_id', (int) $deptId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $pagination = $query->paginate($perPage);

        return ApiResponse::success([
            'data' => ActionPlanResource::collection($pagination->items())->resolve(),
            'current_page' => $pagination->currentPage(),
            'last_page' => $pagination->lastPage(),
            'per_page' => $pagination->perPage(),
            'total' => $pagination->total(),
        ]);
    }

    public function store(StoreActionPlanRequest $request): JsonResponse
    {
        $this->authorize('create', ActionPlan::class);

        $finding = Finding::findOrFail($request->input('finding_id'));

        $service = new ActionPlanService();
        $aps = $service->createForDepartments($finding, $request->validated(), $request->input('department_ids'));

        return ApiResponse::success(ActionPlanResource::collection($aps), 'Action plan berhasil dibuat.', 201);
    }

    public function show(ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('view', $action_plan);

        $action_plan->load(['finding', 'department', 'assignees', 'documents']);

        return ApiResponse::success(new ActionPlanResource($action_plan));
    }

    public function update(UpdateActionPlanRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('update', $action_plan);

        $service = new ActionPlanService();
        $ap = $service->update($action_plan, $request->validated());

        $ap->load(['finding', 'department', 'assignees']);

        return ApiResponse::success(new ActionPlanResource($ap), 'Action plan berhasil diperbarui.');
    }

    public function destroy(ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('delete', $action_plan);

        (new ActionPlanService())->destroy($action_plan);

        return ApiResponse::success(null, 'Action plan berhasil dihapus.');
    }

    public function send(SendActionPlanRequest $request): JsonResponse
    {
        $service = new ActionPlanService();

        foreach ($request->input('ids') as $id) {
            $ap = ActionPlan::findOrFail($id);
            $this->authorize('send', $ap);
        }

        $aps = $service->send($request->input('ids'));

        return ApiResponse::success(ActionPlanResource::collection($aps), 'Action plan berhasil dikirim.');
    }

    public function assignPics(AssignPicsRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('assignPics', $action_plan);

        $service = new ActionPlanService();
        $ap = $service->assignPics($action_plan, $request->input('user_ids'));

        $ap->load(['finding', 'department', 'assignees']);

        return ApiResponse::success(new ActionPlanResource($ap), 'PIC berhasil ditunjuk.');
    }

    public function changeDeadline(UpdateActionPlanDeadlineRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('changeDeadline', $action_plan);

        $service = new ActionPlanService();
        $ap = $service->changeDeadline($action_plan, $request->input('deadline'), $request->input('reason'));

        return ApiResponse::success(new ActionPlanResource($ap), 'Deadline action plan berhasil diubah.');
    }

    public function documents(ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('view', $action_plan);

        return ApiResponse::success(DocumentResource::collection($action_plan->documents));
    }

    public function uploadDocument(UploadActionPlanDocumentRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('uploadDocument', $action_plan);

        $file = $request->file('document');
        $path = $file->store('action-plans', config('upload.disk'));

        $document = $action_plan->documents()->create([
            'label' => $request->input('label'),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return ApiResponse::success(new DocumentResource($document), 'Dokumen berhasil diunggah.', 201);
    }

    public function deleteDocument(ActionPlan $action_plan, Document $document): JsonResponse
    {
        $this->authorize('uploadDocument', $action_plan);
        abort_if(
            $document->documentable_type !== $action_plan->getMorphClass() || $document->documentable_id !== $action_plan->id,
            404
        );

        $document->delete();

        return ApiResponse::success(null, 'Dokumen berhasil dihapus.');
    }

    public function downloadDocument(ActionPlan $action_plan, Document $document): StreamedResponse
    {
        $this->authorize('view', $action_plan);
        abort_if(
            $document->documentable_type !== $action_plan->getMorphClass() || $document->documentable_id !== $action_plan->id,
            404
        );

        return Storage::disk(config('upload.disk'))->download($document->path, $document->name);
    }
}
