<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class FollowUpPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'follow_ups', 'view');
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'follow_ups', 'create');
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        if (!PermissionService::can($user, 'follow_ups', 'update')) {
            return false;
        }

        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return $user->role === Role::StaffDept
            && $followUp->assignees()->whereKey($user->id)->exists();
    }

    public function submit(User $user, FollowUp $followUp): bool
    {
        return PermissionService::can($user, 'follow_ups', 'update');
    }
}