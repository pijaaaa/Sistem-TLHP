<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessSpiReviewRequest;
use App\Http\Requests\CompleteSpiReviewRequest;
use App\Http\Resources\ActionPlanResource;
use App\Models\ActionPlan;
use App\Models\FollowUp;
use App\Models\SpiReview;
use App\Services\SpiReviewService;
use App\Support\ApiResponse;
use App\Support\CacheService;
use Illuminate\Http\JsonResponse;

class SpiReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ActionPlan::class);

        $aps = ActionPlan::query()
            ->where('status', 'DIAJUKAN_KE_SPI')
            ->with(['finding', 'department'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return ApiResponse::success([
            'data' => ActionPlanResource::collection($aps->items())->resolve(),
            'current_page' => $aps->currentPage(),
            'last_page' => $aps->lastPage(),
            'per_page' => $aps->perPage(),
            'total' => $aps->total(),
        ]);
    }

    public function bundle(ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('view', $action_plan);

        $bundle = CacheService::remember('action_plans', 'review_bundle.' . $action_plan->id, function () use ($action_plan) {
            return $this->buildBundle($action_plan);
        }, 120);

        return ApiResponse::success($bundle);
    }

    public function assess(AssessSpiReviewRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('review', $action_plan);

        $review = (new SpiReviewService())->assess($action_plan, $request->input('items'));

        return ApiResponse::success([
            'review_id' => $review->id,
            'assessed_items' => $review->items()->count(),
        ], 'Penilaian disimpan.');
    }

    public function complete(CompleteSpiReviewRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('review', $action_plan);

        $ap = (new SpiReviewService())->complete($action_plan, $request->input('new_deadline'));
        $ap->load(['finding', 'department', 'assignees']);

        return ApiResponse::success(new ActionPlanResource($ap), 'Review SPI selesai.');
    }

    private function buildBundle(ActionPlan $actionPlan): array
    {
        $actionPlan->load(['finding', 'department', 'assignees']);

        $revisions = $actionPlan->revisions()
            ->with('requestedBy')
            ->get()
            ->map(fn ($r) => [
                'revision_no' => $r->revision_no,
                'label' => $r->revision_no === 0 ? 'Awal' : "Revisi {$r->revision_no}",
                'source' => $r->source->value,
                'source_label' => $r->source->label(),
                'reason' => $r->reason,
                'requested_by' => $r->requestedBy?->name,
                'requested_role' => $r->requested_role,
                'requested_at' => $r->requested_at?->toDateTimeString(),
                'forwarded_to_pic_at' => $r->forwarded_to_pic_at?->toDateTimeString(),
                'new_deadline' => $r->new_deadline?->toDateString(),
            ])
            ->values();

        $followUps = FollowUp::withoutGlobalScopes()
            ->where('action_plan_id', $actionPlan->id)
            ->with([
                'assignees',
                'progress_reports' => fn ($q) => $q->with(['reporter', 'documents']),
                'reviews.reviewer',
                'comments.author',
            ])
            ->orderBy('revision_no')
            ->orderBy('id')
            ->get();

        $spiItems = SpiReview::where('action_plan_id', $actionPlan->id)
            ->with('items')
            ->get()
            ->flatMap(fn ($review) => $review->items->map(fn ($item) => [
                'key' => $item->follow_up_id . '.rev' . $review->revision_no,
                'result' => $item->result->value,
                'result_label' => $item->result->label(),
                'note' => $item->note,
            ]))
            ->keyBy('key');

        $grouped = [];
        foreach ($followUps as $fu) {
            $spiKey = $fu->id . '.rev' . $fu->revision_no;
            $grouped[$fu->revision_no][] = [
                'id' => $fu->id,
                'revision_no' => $fu->revision_no,
                'description' => $fu->description,
                'target_date' => $fu->target_date?->toDateString(),
                'weight' => (int) $fu->weight,
                'progress' => (int) $fu->progress,
                'status' => $fu->status->value,
                'status_label' => $fu->status->label(),
                'linked_follow_up_id' => $fu->linked_follow_up_id,
                'assignees' => $fu->assignees->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                'progress_reports' => $fu->progress_reports->map(fn ($p) => [
                    'id' => $p->id,
                    'progress_value' => (int) $p->progress_value,
                    'note' => $p->note,
                    'reported_at' => $p->reported_at?->toDateTimeString(),
                    'reporter' => $p->reporter?->name,
                    'documents' => $p->documents->map(fn ($d) => [
                        'id' => $d->id,
                        'label' => $d->label,
                        'name' => $d->name,
                        'size' => (int) $d->size,
                    ])->values(),
                ])->values(),
                'reviews' => $fu->reviews->map(fn ($r) => [
                    'decision' => $r->decision->value,
                    'decision_label' => $r->decision->label(),
                    'note' => $r->note,
                    'weight_before' => (int) $r->weight_before,
                    'weight_after' => (int) $r->weight_after,
                    'reviewer' => $r->reviewer?->name,
                    'created_at' => $r->created_at?->toDateTimeString(),
                ])->values(),
                'comments' => $fu->comments->map(fn ($c) => [
                    'kind' => $c->kind->value,
                    'kind_label' => $c->kind->label(),
                    'body' => $c->body,
                    'author' => $c->author?->name,
                    'created_at' => $c->created_at?->toDateTimeString(),
                ])->values(),
                'spi_item' => $spiItems->get($spiKey) ?? null,
            ];
        }

        return [
            'action_plan' => (new ActionPlanResource($actionPlan))->resolve(),
            'revisions' => $revisions,
            'follow_ups' => $grouped,
        ];
    }
}