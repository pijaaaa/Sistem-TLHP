<?php

namespace App\Services;

use App\Enums\FindingStatus;
use App\Models\ActionPlan;
use App\Models\Finding;
use Illuminate\Validation\ValidationException;

class ActionPlanService
{
    public function createForDepartments(Finding $finding, array $data, array $department_ids): array
    {
        if (!in_array($finding->status->value, [FindingStatus::Terdaftar->value, FindingStatus::ProsessTindakLanjut->value])) {
            throw ValidationException::withMessages([
                'status' => 'Temuan harus berstatus Terdaftar atau Proses Tindak Lanjut.',
            ]);
        }

        if (empty($department_ids)) {
            throw ValidationException::withMessages([
                'departments' => 'Minimal satu departemen auditee harus dipilih.',
            ]);
        }

        $action_plans = [];
        foreach ($department_ids as $dept_id) {
            $dept = $finding->auditee_departments()->find($dept_id);
            if (!$dept) {
                throw ValidationException::withMessages([
                    'departments' => "Departemen $dept_id bukan auditee temuan ini.",
                ]);
            }

            $code = $this->generateCode($finding);
            $ap_data = array_merge($data, [
                'finding_id' => $finding->id,
                'department_id' => $dept_id,
                'code' => $code,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);

            if (isset($data['deadline_per_department'][$dept_id])) {
                $ap_data['deadline'] = $data['deadline_per_department'][$dept_id];
            }

            $action_plans[] = ActionPlan::create($ap_data);
        }

        return $action_plans;
    }

    public function send(array $ap_ids): array
    {
        $action_plans = ActionPlan::whereIn('id', $ap_ids)->where('status', 'DRAFT')->get();

        if ($action_plans->count() !== count($ap_ids)) {
            throw ValidationException::withMessages([
                'status' => 'Semua action plan harus berstatus DRAFT.',
            ]);
        }

        foreach ($action_plans as $ap) {
            $ap->update([
                'status' => 'MENUNGGU_PENENTUAN_PIC',
                'sent_at' => now(),
            ]);
        }

        return $action_plans->toArray();
    }

    public function assignPics(ActionPlan $ap, array $user_ids): ActionPlan
    {
        if (empty($user_ids)) {
            throw ValidationException::withMessages([
                'pics' => 'Minimal satu PIC harus ditunjuk.',
            ]);
        }

        $users = \App\Models\User::whereIn('id', $user_ids)
            ->where('department_id', $ap->department_id)
            ->where('role', 'staff_dept')
            ->where('is_active', true)
            ->get();

        if ($users->count() !== count($user_ids)) {
            throw ValidationException::withMessages([
                'pics' => 'Semua PIC harus staff_dept aktif di departemen terkait.',
            ]);
        }

        $ap->assignees()->sync($user_ids, false);

        if ($ap->status === 'MENUNGGU_PENENTUAN_PIC') {
            $ap->update(['status' => 'PROSES_TINDAK_LANJUT']);
        }

        return $ap->refresh();
    }

    private function generateCode(Finding $finding): string
    {
        $count = ActionPlan::where('finding_id', $finding->id)->count();
        return "{$finding->registration_number}/AP-" . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }
}
