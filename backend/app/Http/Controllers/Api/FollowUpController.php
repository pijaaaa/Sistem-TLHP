<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFollowUpsRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\SubmitFollowUpsRequest;
use App\Http\Requests\UpdateFollowUpRequest;
use App\Http\Requests\OverrideWeightRequest;
use App\Http\Requests\ReviewNoteRequest;
use App\Http\Resources\FollowUpResource;
use App\Http\Resources\FollowUpReviewResource;
use App\Http\Resources\FollowUpCommentResource;
use App\Enums\CommentKind;
use App\Models\ActionPlan;
use App\Models\FollowUp;
use App\Services\FollowUpService;
use App\Services\FollowUpReviewService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FollowUp::class);

        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = FollowUp::query()->with(['actionPlan', 'assignees'])->orderBy('id', 'desc');

        if ($actionPlanId = $request->query('action_plan_id')) {
            $query->where('action_plan_id', (int) $actionPlanId);
        }

        if ($findingId = $request->query('finding_id')) {
            $query->whereHas('actionPlan', fn ($q) => $q->where('finding_id', (int) $findingId));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $pagination = $query->paginate($perPage);

        return ApiResponse::success([
            'data' => FollowUpResource::collection($pagination->items())->resolve(),
            'current_page' => $pagination->currentPage(),
            'last_page' => $pagination->lastPage(),
            'per_page' => $pagination->perPage(),
            'total' => $pagination->total(),
        ]);
    }

    public function store(StoreFollowUpsRequest $request, ActionPlan $action_plan): JsonResponse
    {
        $this->authorize('create', FollowUp::class);

        $followUps = (new FollowUpService())->createBatch($action_plan, $request->input('rows'));

        return ApiResponse::success(
            collect($followUps)->map(fn ($fu) => new FollowUpResource($fu))->all(),
            'Tindak lanjut draft berhasil disimpan.',
            201,
        );
    }

    public function show(FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $follow_up->load(['actionPlan', 'assignees']);

        return ApiResponse::success(new FollowUpResource($follow_up));
    }

    public function update(UpdateFollowUpRequest $request, FollowUp $follow_up): JsonResponse
    {
        $this->authorize('update', $follow_up);

        $followUp = (new FollowUpService())->update($follow_up, $request->validated());

        return ApiResponse::success(new FollowUpResource($followUp), 'Tindak lanjut berhasil diperbarui.');
    }

    public function submit(SubmitFollowUpsRequest $request): JsonResponse
    {
        $followUps = (new FollowUpService())->submit($request->input('ids'));

        return ApiResponse::success(
            FollowUpResource::collection($followUps),
            'Tindak lanjut berhasil diajukan ke manager.',
        );
    }

    public function approve(FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $followUp = (new FollowUpReviewService())->approve($follow_up);

        return ApiResponse::success(new FollowUpResource($followUp), 'Tindak lanjut disetujui.');
    }

    public function revision(ReviewNoteRequest $request, FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $followUp = (new FollowUpReviewService())->requestRevision($follow_up, $request->input('note'));

        return ApiResponse::success(new FollowUpResource($followUp), 'Permintaan revisi dikirim ke PIC.');
    }

    public function reject(ReviewNoteRequest $request, FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $followUp = (new FollowUpReviewService())->reject($follow_up, $request->input('note'));

        return ApiResponse::success(new FollowUpResource($followUp), 'Tindak lanjut ditolak.');
    }

    public function returnToRevision(ReviewNoteRequest $request, FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $followUp = (new FollowUpReviewService())->returnToRevision($follow_up, $request->input('note'));

        return ApiResponse::success(new FollowUpResource($followUp), 'Tindak lanjut dikembalikan ke revisi.');
    }

    public function overrideWeight(OverrideWeightRequest $request, FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $followUp = (new FollowUpReviewService())->overrideWeight($follow_up, (int) $request->input('weight'));

        return ApiResponse::success(new FollowUpResource($followUp), 'Bobot tindak lanjut diperbarui.');
    }

    public function reviews(FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $reviews = $follow_up->reviews()->with('reviewer')->get();

        return ApiResponse::success(FollowUpReviewResource::collection($reviews));
    }

    public function comments(FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $comments = $follow_up->comments()->with('author')->get();

        return ApiResponse::success(FollowUpCommentResource::collection($comments));
    }

    public function addComment(StoreCommentRequest $request, FollowUp $follow_up): JsonResponse
    {
        $this->authorize('view', $follow_up);

        $comment = (new FollowUpReviewService())->addComment(
            $follow_up,
            CommentKind::from($request->input('kind')),
            $request->input('body'),
        );

        return ApiResponse::success(new FollowUpCommentResource($comment), 'Komentar ditambahkan.', 201);
    }
}