<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Services\ActionPlanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $aps = ActionPlan::paginate($perPage);

        return ApiResponse::success($aps);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'finding_id' => 'required|exists:findings,id',
            'department_ids' => 'required|array|min:1',
            'title' => 'required|string',
            'condition' => 'nullable|string',
            'criteria' => 'nullable|string',
            'cause' => 'nullable|string',
            'impact' => 'nullable|string',
            'risk' => 'nullable|in:RENDAH,SEDANG,TINGGI,KRITIS',
            'deadline' => 'nullable|date',
            'loss_idr' => 'nullable|numeric',
            'loss_usd' => 'nullable|numeric',
        ]);

        $finding = Finding::findOrFail($request->input('finding_id'));
        $service = new ActionPlanService();
        $aps = $service->createForDepartments($finding, $request->validated(), $request->input('department_ids'));

        return ApiResponse::success($aps, 'Action plan berhasil dibuat.', 201);
    }

    public function show(ActionPlan $ap): JsonResponse
    {
        $ap->load('finding', 'department', 'assignees');

        return ApiResponse::success($ap);
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:action_plans,id',
        ]);

        $service = new ActionPlanService();
        $aps = $service->send($request->input('ids'));

        return ApiResponse::success($aps, 'Action plan berhasil dikirim.');
    }

    public function assignPics(Request $request, ActionPlan $ap): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $service = new ActionPlanService();
        $ap = $service->assignPics($ap, $request->input('user_ids'));

        return ApiResponse::success($ap, 'PIC berhasil ditunjuk.');
    }
}
