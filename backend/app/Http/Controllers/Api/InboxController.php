<?php

namespace App\Http\Controllers\Api;

use App\Enums\InboxTaskStatus;
use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Services\TaskDispatcher;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = \App\Models\InboxTask::where('recipient_id', auth()->id())
            ->with(['creator'])
            ->orderByDesc('received_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('task_type')) {
            $query->where('task_type', $type);
        }

        $pagination = $query->paginate(min((int) $request->query('per_page', 15), 100));

        return ApiResponse::success([
            'data' => collect($pagination->items())->map(fn ($task) => [
                'id' => $task->id,
                'task_type' => $task->task_type->value,
                'task_type_label' => $task->task_type->label(),
                'subject_type' => $task->subject_type,
                'subject_id' => $task->subject_id,
                'title' => $task->title,
                'received_at' => $task->received_at?->toDateTimeString(),
                'first_opened_at' => $task->first_opened_at?->toDateTimeString(),
                'acted_at' => $task->acted_at?->toDateTimeString(),
                'status' => $task->status->value,
            ])->values(),
            'current_page' => $pagination->currentPage(),
            'last_page' => $pagination->lastPage(),
            'per_page' => $pagination->perPage(),
            'total' => $pagination->total(),
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'integer'],
        ]);

        $type = $validated['subject_type'];
        $subject = match (true) {
            str_contains($type, 'Finding') => Finding::find($validated['subject_id']),
            str_contains($type, 'ActionPlan') => ActionPlan::find($validated['subject_id']),
            str_contains($type, 'FollowUp') => FollowUp::find($validated['subject_id']),
            $type === 'finding' => Finding::find($validated['subject_id']),
            $type === 'action_plan' => ActionPlan::find($validated['subject_id']),
            $type === 'follow_up' => FollowUp::find($validated['subject_id']),
            default => null,
        };

        if (!$subject) {
            throw ValidationException::withMessages([
                'subject_type' => 'Subjek tidak dikenal.',
            ]);
        }

        $touched = TaskDispatcher::markOpened($subject);

        return ApiResponse::success(['opened' => $touched]);
    }
}