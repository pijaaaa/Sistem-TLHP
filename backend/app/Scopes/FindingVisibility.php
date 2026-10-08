<?php

namespace App\Scopes;

use App\Enums\FindingStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FindingVisibility implements Scope
{
    public function apply(Builder $query, Model $model): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $role = $user->role->value;

        if ($role === Role::SuperAdmin->value || $user->role->isMonitor()) {
            return;
        }

        if ($role === Role::ManagerDept->value) {
            $query->whereExists(function ($q) use ($user) {
                $q->selectRaw('1')
                    ->from('action_plans')
                    ->whereColumn('action_plans.finding_id', 'findings.id')
                    ->where('action_plans.department_id', $user->department_id)
                    ->whereNotNull('action_plans.sent_at')
                    ->whereNull('action_plans.deleted_at');
            });
            return;
        }

        if ($role === Role::StaffDept->value) {
            $query->whereExists(function ($q) use ($user) {
                $q->selectRaw('1')
                    ->from('action_plans')
                    ->join('action_plan_assignees', 'action_plan_assignees.action_plan_id', '=', 'action_plans.id')
                    ->whereColumn('action_plans.finding_id', 'findings.id')
                    ->where('action_plan_assignees.user_id', $user->id)
                    ->whereNotNull('action_plans.sent_at')
                    ->whereNull('action_plans.deleted_at');
            })
            ->where('findings.status', '!=', FindingStatus::Closed->value);
            return;
        }

        $query->whereRaw('1=0');
    }
}
