<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordExternalStatusRequest;
use App\Http\Resources\FindingResource;
use App\Models\Document;
use App\Models\ExternalStatusRecord;
use App\Models\Finding;
use App\Services\ExternalStatusService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExternalStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Finding::class);

        $findings = Finding::query()
            ->where('status', 'MENUNGGU_STATUS_EKSTERNAL')
            ->with('auditee_departments')
            ->withCount('action_plans')
            ->withCount('documents')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return ApiResponse::success([
            'data' => FindingResource::collection($findings->items())->resolve(),
            'current_page' => $findings->currentPage(),
            'last_page' => $findings->lastPage(),
            'per_page' => $findings->perPage(),
            'total' => $findings->total(),
        ]);
    }

    public function records(Finding $finding): JsonResponse
    {
        $this->authorize('view', $finding);

        $records = ExternalStatusRecord::where('finding_id', $finding->id)
            ->with(['recorder', 'actionPlans', 'documents'])
            ->orderByDesc('recorded_at')
            ->get();

        return ApiResponse::success($records->map(fn ($r) => [
            'id' => $r->id,
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'note' => $r->note,
            'recorded_by' => $r->recorded_by,
            'recorder' => $r->recorder ? ['id' => $r->recorder->id, 'name' => $r->recorder->name] : null,
            'recorded_at' => $r->recorded_at?->toDateTimeString(),
            'action_plans' => $r->actionPlans->map(fn ($ap) => [
                'id' => $ap->id,
                'code' => $ap->code,
                'title' => $ap->title,
            ])->values(),
            'documents' => $r->documents->map(fn ($d) => [
                'id' => $d->id,
                'label' => $d->label,
                'name' => $d->name,
                'size' => (int) $d->size,
            ])->values(),
        ])->values());
    }

    public function store(RecordExternalStatusRequest $request, Finding $finding): JsonResponse
    {
        $this->authorize('view', $finding);

        $documents = array_values(array_filter(array_keys($request->input('document', [])), fn ($i) => $request->file("document.{$i}.file") !== null));

        $documents = array_map(fn ($i) => [
            'file' => $request->file("document.{$i}.file"),
            'label' => $request->input("document.{$i}.label"),
        ], $documents);

        $record = (new ExternalStatusService())->record(
            $finding,
            \App\Enums\ExternalStatus::from($request->input('status')),
            $request->input('note'),
            $documents,
            $request->input('action_plan_ids', []),
            $request->input('new_deadline'),
        );

        return ApiResponse::success(['id' => $record->id], 'Status eksternal berhasil dicatat.', 201);
    }

    public function downloadDocument(Finding $finding, ExternalStatusRecord $record, Document $document): StreamedResponse
    {
        $this->authorize('view', $finding);
        abort_if($record->finding_id !== $finding->id, 404);
        abort_if(
            $document->documentable_type !== $record->getMorphClass() || $document->documentable_id !== $record->id,
            404
        );

        return Storage::disk(config('upload.disk'))->download($document->path, $document->name);
    }
}