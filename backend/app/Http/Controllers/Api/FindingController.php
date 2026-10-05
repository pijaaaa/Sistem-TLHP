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

        $paginator = FindingService::paginated($perPage);
        $data = FindingResource::collection($paginator)
            ->toResponse($request)
            ->getData(true);

        return ApiResponse::success($data);
    }

    public function store(StoreFindingRequest $request): JsonResponse
    {
        $finding = FindingService::create($request->validated());

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil ditambahkan.', 201);
    }

    public function show(Finding $finding): JsonResponse
    {
        $finding->load('documents');

        return ApiResponse::success(new FindingResource($finding));
    }

    public function update(UpdateFindingRequest $request, Finding $finding): JsonResponse
    {
        $finding = FindingService::update($finding, $request->validated());

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil diperbarui.');
    }

    public function destroy(Finding $finding): JsonResponse
    {
        FindingService::delete($finding);

        return ApiResponse::success(null, 'Temuan berhasil dihapus.');
    }

    public function sendToIa(Finding $finding): JsonResponse
    {
        $finding = FindingService::sendToIA($finding);

        return ApiResponse::success(new FindingResource($finding), 'Temuan berhasil dikirim ke IA.');
    }

    public function documents(Finding $finding): JsonResponse
    {
        return ApiResponse::success(FindingDocumentResource::collection($finding->documents));
    }

    public function uploadDocument(UploadFindingDocumentRequest $request, Finding $finding): JsonResponse
    {
        $document = FindingService::uploadDocument($finding, $request->file('document'), $request->string('label'));

        return ApiResponse::success(new FindingDocumentResource($document), 'Dokumen berhasil diunggah.', 201);
    }

    public function deleteDocument(Finding $finding, FindingDocument $document): JsonResponse
    {
        abort_if($document->finding_id !== $finding->id, 404);
        FindingService::deleteDocument($document);

        return ApiResponse::success(null, 'Dokumen berhasil dihapus.');
    }
}
