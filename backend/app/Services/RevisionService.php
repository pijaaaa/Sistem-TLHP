<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\Role;
use App\Events\RevisionForwarded;
use App\Models\ActionPlan;
use App\Models\ActionPlanRevision;
use App\Support\AuditLogger;
use App\Support\CacheService;
use Illuminate\Validation\ValidationException;

class RevisionService
{
    public function forwardToPic(ActionPlan $actionPlan): ActionPlan
    {
        $user = auth()->user();
        if ($user->role !== Role::ManagerDept || $actionPlan->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'action_plan_id' => 'Hanya manager departemen pemilik action plan yang dapat meneruskan revisi.',
            ]);
        }

        if ($actionPlan->status !== ActionPlanStatus::RevisiSpi) {
            throw ValidationException::withMessages([
                'status' => 'Hanya action plan berstatus Revisi SPI yang dapat diteruskan ke PIC.',
            ]);
        }

        $revision = ActionPlanRevision::where('action_plan_id', $actionPlan->id)
            ->where('revision_no', $actionPlan->current_revision)
            ->whereNull('forwarded_to_pic_at')
            ->latest('id')
            ->first();

        $data = ['status' => ActionPlanStatus::ProsesTindakLanjut];

        if ($revision) {
            $revision->update(['forwarded_to_pic_at' => now()]);

            if ($revision->new_deadline) {
                $data['deadline'] = $revision->new_deadline->toDateString();
            }
        }

        $actionPlan->update($data);

        AuditLogger::log('revision.forwarded_to_pic', auth()->id(), request()?->ip(), "Revisi action plan {$actionPlan->code} diteruskan ke PIC.", [
            'action_plan_id' => $actionPlan->id,
        ]);

        RevisionForwarded::dispatch($actionPlan);

        CacheService::flushGroup('action_plans');
        CacheService::flushGroup('findings');

        return $actionPlan->refresh();
    }
}