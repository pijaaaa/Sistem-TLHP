<?php

namespace App\Policies;

use App\Enums\ActionPlanStatus;
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

    public function view(User $user, ActionPlan $actionPlan): bool
    {
        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin])) {
            return true;
        }

        $actionPlan->loadMissing('findingDepartment');
        $fd = $actionPlan->findingDepartment;

        if ($user->role === Role::ManagerDept && $fd->department_id === $user->department_id) {
            return true;
        }

        if ($user->role === Role::StaffDept && $actionPlan->created_by === $user->id) {
            return true;
        }

        if ($user->role === Role::ManagerSpi) {
            return in_array($fd->finding->status, [\App\Enums\FindingStatus::Closed, \App\Enums\FindingStatus::CaseClosed]);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'action_plans', 'create');
    }

    public function update(User $user, ActionPlan $actionPlan): bool
    {
        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin])) {
            return true;
        }

        $actionPlan->loadMissing('findingDepartment');
        $fd = $actionPlan->findingDepartment;

        if ($user->role === Role::ManagerDept && $fd->department_id === $user->department_id) {
            return true;
        }

        if ($user->role === Role::StaffDept && $actionPlan->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, ActionPlan $actionPlan): bool
    {
        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin])) {
            return true;
        }

        if ($user->role === Role::StaffDept && $actionPlan->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function submit(User $user, ActionPlan $actionPlan): bool
    {
        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin])) {
            return true;
        }

        $actionPlan->loadMissing('findingDepartment');
        $fd = $actionPlan->findingDepartment;

        if ($user->role === Role::ManagerDept && $fd->department_id === $user->department_id) {
            return true;
        }

        if ($user->role === Role::StaffDept && $actionPlan->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function uploadDocument(User $user, ActionPlan $actionPlan): bool
    {
        return $this->update($user, $actionPlan);
    }
}
