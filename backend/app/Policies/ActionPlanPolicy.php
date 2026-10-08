<?php

namespace App\Policies;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActionPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'action_plans', 'view');
    }

    public function view(User $user, ActionPlan $ap): bool
    {
        if (!$this->viewAny($user)) {
            return false;
        }

        return match ($user->role) {
            Role::SuperAdmin, Role::AdminSpi, Role::InternalAudit, Role::Kepala_spi, Role::ManagerIa, Role::Direksi => true,
            Role::ManagerDept => $ap->department_id === $user->department_id && $ap->sent_at !== null,
            Role::StaffDept => $ap->sent_at !== null
                && $ap->assignees()->whereKey($user->id)->exists()
                && $ap->finding?->status !== FindingStatus::Closed,
        };
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'action_plans', 'create');
    }

    public function update(User $user, ActionPlan $ap): bool
    {
        return $ap->status === ActionPlanStatus::Draft
            && PermissionService::can($user, 'action_plans', 'update');
    }

    public function delete(User $user, ActionPlan $ap): bool
    {
        return $ap->status === ActionPlanStatus::Draft
            && PermissionService::can($user, 'action_plans', 'delete');
    }

    public function send(User $user, ActionPlan $ap): bool
    {
        return $ap->status === ActionPlanStatus::Draft
            && PermissionService::can($user, 'action_plans', 'create');
    }

    public function assignPics(User $user, ActionPlan $ap): bool
    {
        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return $user->role === Role::ManagerDept
            && $ap->department_id === $user->department_id
            && $ap->sent_at !== null
            && $ap->status !== ActionPlanStatus::Closed;
    }

    public function changeDeadline(User $user, ActionPlan $ap): bool
    {
        return $user->role === Role::SuperAdmin
            || ($user->role === Role::AdminSpi && $ap->status !== ActionPlanStatus::Closed);
    }

    public function submitToSpi(User $user, ActionPlan $ap): bool
    {
        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return $user->role === Role::ManagerDept
            && $ap->department_id === $user->department_id
            && $ap->status === ActionPlanStatus::ProsesTindakLanjut;
    }

    public function review(User $user, ActionPlan $ap): bool
    {
        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return $user->role === Role::AdminSpi && $ap->status === ActionPlanStatus::DiajukanKeSpi;
    }

    public function forwardToPic(User $user, ActionPlan $ap): bool
    {
        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return $user->role === Role::ManagerDept
            && $ap->department_id === $user->department_id
            && $ap->status === ActionPlanStatus::RevisiSpi;
    }

    public function uploadDocument(User $user, ActionPlan $ap): bool
    {
        return PermissionService::can($user, 'action_plans', 'update')
            && $ap->status !== ActionPlanStatus::Closed;
    }
}
