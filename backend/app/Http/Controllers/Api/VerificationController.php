<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditorConclusion;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordVerificationRequest;
use App\Http\Resources\FindingResource;
use App\Http\Resources\FindingVerificationResource;
use App\Models\Finding;
use App\Services\VerificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Finding::class);

        $perPage = (int) $request->query('per_page', 15);

        $paginator = VerificationService::pending($perPage);
        $data = FindingResource::collection($paginator)
            ->toResponse($request)
            ->getData(true);

        return ApiResponse::success($data);
    }

    public function store(RecordVerificationRequest $request, Finding $finding): JsonResponse
    {
        $this->authorize('verify', $finding);

        $conclusion = AuditorConclusion::from($request->validated('auditor_conclusion'));

        $verification = VerificationService::record($finding, $conclusion, $request->validated());

        $message = $conclusion->closesFinding()
            ? 'Temuan berhasil ditutup.'
            : 'Temuan dikembalikan ke Manager IA untuk ronde baru.';

        return ApiResponse::success([
            'verification' => new FindingVerificationResource($verification),
            'finding' => new FindingResource($finding->fresh()),
        ], $message, 201);
    }

    public function show(Finding $finding): JsonResponse
    {
        $this->authorize('view', $finding);

        return ApiResponse::success(
            FindingVerificationResource::collection($finding->verifications()->latest('id')->get()),
        );
    }
}