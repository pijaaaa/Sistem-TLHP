<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFindingRequest;
use App\Http\Requests\UpdateFindingRequest;
use App\Http\Requests\UploadFindingDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\FindingResource;
use App\Models\Document;
use App\Models\Finding;
use App\Services\FindingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FindingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Finding::class);

        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = Finding::query()->with('auditee_departments')->withCount('documents')->orderBy('id', 'desc');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($fiscalYear = $request->query('fiscal_year')) {
            $query->where('fiscal_year', (int) $fiscalYear);
        }
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }
        if ($departmentId = $request->query('department_id')) {
            $query->whereHas('auditee_departments', fn ($q) => $q->where('departments.id', (int) $departmentId));
        }
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('lhp_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        return ApiResponse::success($query->paginate($perPage));
    }

    public function store(StoreFindingRequest $request): JsonResponse
    {
        $this->authorize('create', Finding::class);

        $finding = (new FindingService())->createDraft($request->validated());

        return ApiResponse::success(new FindingResource($finding), 'Temuan draft berhasil dibuat.', 201);
    }

    public function show(Finding $finding): JsonResponse
    {
        $this->authorize('view', $finding);

        $finding->load('auditee_departments')->loadCount('documents');

        return ApiResponse::success(new FindingResource($finding));
    }

    public function update(UpdateFindingRequest $request, Finding $finding): JsonResponse
    {
        $this->authorize('update', $finding);

        $finding = (new FindingService())->update($finding, $request->validated());

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil diperbarui.');
    }

    public function register(Request $request, Finding $finding): JsonResponse
    {
        $this->authorize('register', $finding);

        $request->validate([
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        $finding = (new FindingService())->register($finding, $request->input('department_ids'));

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil didaftarkan.');
    }

    public function activate(Finding $finding): JsonResponse
    {
        $this->authorize('activate', $finding);

        $finding = (new FindingService())->activate($finding);

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil diaktifkan.');
    }

    public function destroy(Finding $finding): JsonResponse
    {
        $this->authorize('delete', $finding);

        $finding->delete();

        return ApiResponse::success(null, 'Temuan berhasil dihapus.');
    }

    public function documents(Finding $finding): JsonResponse
    {
        $this->authorize('view', $finding);

        return ApiResponse::success(DocumentResource::collection($finding->documents));
    }

    public function uploadDocument(UploadFindingDocumentRequest $request, Finding $finding): JsonResponse
    {
        $this->authorize('update', $finding);

        $file = $request->file('document');
        $path = $file->store('findings', config('upload.disk'));

        $document = $finding->documents()->create([
            'label' => $request->input('label'),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return ApiResponse::success(new DocumentResource($document), 'Dokumen berhasil diunggah.', 201);
    }

    public function deleteDocument(Finding $finding, Document $document): JsonResponse
    {
        $this->authorize('update', $finding);
        abort_if(
            $document->documentable_type !== $finding->getMorphClass() || $document->documentable_id !== $finding->id,
            404
        );

        $document->delete();

        return ApiResponse::success(null, 'Dokumen berhasil dihapus.');
    }

    public function downloadDocument(Finding $finding, Document $document): StreamedResponse
    {
        $this->authorize('view', $finding);
        abort_if(
            $document->documentable_type !== $finding->getMorphClass() || $document->documentable_id !== $finding->id,
            404
        );

        return Storage::disk(config('upload.disk'))->download($document->path, $document->name);
    }
}
