<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'audit_trail', 'view');
    }

    public function view(User $user, Audit $audit): bool
    {
        return $this->viewAny($user);
    }
}