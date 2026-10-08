<?php

namespace App\Scopes;

use App\Enums\FindingStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActionPlanVisibility implements Scope
{
    public function apply(Builder $query, Model $model): void
    {
        $user = auth()->user();
        if (!$user) {
            $query->whereRaw('1=0');
            return;
        }

        if ($user->role->value === Role::SuperAdmin->value) {
            return;
        }

        if (in_array($user->role->value, [Role::AdminSpi->value, Role::InternalAudit->value, Role::Kepala_spi->value])) {
            return;
        }

        if ($user->role->value === Role::ManagerIa->value || $user->role->value === Role::Direksi->value) {
            return;
        }

        if ($user->role->value === Role::ManagerDept->value) {
            $query->where('department_id', $user->department_id)
                ->where('sent_at', '!=', null);
            return;
        }

        if ($user->role->value === Role::StaffDept->value) {
            $query->whereIn('id', function ($q) use ($user) {
                $q->select('action_plan_id')
                    ->from('action_plan_assignees')
                    ->where('user_id', $user->id);
            })
            ->where('sent_at', '!=', null)
            ->whereHas('finding', function ($q) {
                $q->where('status', '!=', FindingStatus::Closed->value);
            });
            return;
        }

        $query->whereRaw('1=0');
    }
}
