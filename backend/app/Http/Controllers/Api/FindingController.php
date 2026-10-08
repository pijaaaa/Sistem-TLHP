<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFindingRequest;
use App\Http\Requests\UpdateFindingRequest;
use App\Http\Requests\UploadFindingDocumentRequest;
use App\Http\Resources\FindingDocumentResource;
use App\Http\Resources\FindingResource;
use App\Models\Finding;
use App\Models\FindingDocument;
use App\Services\FindingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $findings = Finding::paginate($perPage);

        return ApiResponse::success($findings);
    }

    public function store(StoreFindingRequest $request): JsonResponse
    {
        $service = new FindingService();
        $finding = $service->createDraft($request->validated());

        return ApiResponse::success(new FindingResource($finding), 'Temuan draft berhasil dibuat.', 201);
    }

    public function show(Finding $finding): JsonResponse
    {
        $finding->load('auditee_departments');

        return ApiResponse::success(new FindingResource($finding));
    }

    public function update(UpdateFindingRequest $request, Finding $finding): JsonResponse
    {
        $service = new FindingService();
        $finding = $service->update($finding, $request->validated());

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil diperbarui.');
    }

    public function register(Request $request, Finding $finding): JsonResponse
    {
        $request->validate([
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        $service = new FindingService();
        $finding = $service->register($finding, $request->input('department_ids'));

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil didaftarkan.');
    }

    public function activate(Finding $finding): JsonResponse
    {
        $service = new FindingService();
        $finding = $service->activate($finding);

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil diaktifkan.');
    }

    public function destroy(Finding $finding): JsonResponse
    {
        if ($finding->status->value !== 'draft') {
            return ApiResponse::error('Hanya temuan draft yang dapat dihapus.', 422);
        }

        $finding->delete();
        return ApiResponse::success(null, 'Temuan berhasil dihapus.');
    }

    public function documents(Finding $finding): JsonResponse
    {
        return ApiResponse::success(FindingDocumentResource::collection($finding->documents));
    }

    public function uploadDocument(UploadFindingDocumentRequest $request, Finding $finding): JsonResponse
    {
        $doc = FindingDocument::create([
            'finding_id' => $finding->id,
            'label' => $request->input('label'),
            'filename' => $request->file('document')->getClientOriginalName(),
            'file_path' => $request->file('document')->store('findings'),
            'mime_type' => $request->file('document')->getMimeType(),
        ]);

        return ApiResponse::success(new FindingDocumentResource($doc), 'Dokumen berhasil diunggah.', 201);
    }

    public function deleteDocument(Finding $finding, FindingDocument $document): JsonResponse
    {
        abort_if($document->finding_id !== $finding->id, 404);
        $document->delete();

        return ApiResponse::success(null, 'Dokumen berhasil dihapus.');
    }
}
