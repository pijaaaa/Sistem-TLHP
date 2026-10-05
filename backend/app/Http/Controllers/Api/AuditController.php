<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditResource;
use App\Models\Audit;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $perPage = min((int) $request->query('per_page', 25), 100);

        $query = Audit::query()->with('user')->orderBy('id', 'desc');

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($entityType = $request->query('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', (int) $entityId);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $paginator = $query->paginate($perPage);

        $data = AuditResource::collection($paginator)->toResponse($request)->getData(true);

        return ApiResponse::success($data);
    }

    public function actions(): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $actions = Audit::query()->distinct()->orderBy('action')->pluck('action');

        return ApiResponse::success($actions);
    }
}