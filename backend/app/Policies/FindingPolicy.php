<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Finding;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class FindingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'findings.reports', 'view')
            || PermissionService::can($user, 'findings.list', 'view');
    }

    public function view(User $user, Finding $finding): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'findings.reports', 'create');
    }

    public function update(User $user, Finding $finding): bool
    {
        return PermissionService::can($user, 'findings.reports', 'update')
            || $finding->isEditableByAdmin();
    }

    public function delete(User $user, Finding $finding): bool
    {
        return PermissionService::can($user, 'findings.reports', 'delete');
    }

    public function assess(User $user, Finding $finding): bool
    {
        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return $user->role === Role::ManagerIa
            && PermissionService::can($user, 'assessments', 'update');
    }
}
