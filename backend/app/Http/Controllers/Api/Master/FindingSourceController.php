<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFindingSourceRequest;
use App\Http\Requests\UpdateFindingSourceRequest;
use App\Http\Resources\FindingSourceResource;
use App\Models\FindingSource;
use App\Services\FindingSourceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingSourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        return ApiResponse::success(
            FindingSourceResource::collection(FindingSourceService::paginated($perPage))
        );
    }

    public function store(StoreFindingSourceRequest $request): JsonResponse
    {
        $source = FindingSourceService::create($request->validated());

        return ApiResponse::success(new FindingSourceResource($source), 'Sumber temuan berhasil ditambahkan.', 201);
    }

    public function show(FindingSource $finding_source): JsonResponse
    {
        return ApiResponse::success(new FindingSourceResource($finding_source));
    }

    public function update(UpdateFindingSourceRequest $request, FindingSource $finding_source): JsonResponse
    {
        $source = FindingSourceService::update($finding_source, $request->validated());

        return ApiResponse::success(new FindingSourceResource($source), 'Sumber temuan berhasil diperbarui.');
    }

    public function destroy(FindingSource $finding_source): JsonResponse
    {
        FindingSourceService::delete($finding_source);

        return ApiResponse::success(null, 'Sumber temuan berhasil dihapus.');
    }
}
