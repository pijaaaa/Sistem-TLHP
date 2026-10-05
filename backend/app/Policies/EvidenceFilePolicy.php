<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\EvidenceFile;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvidenceFilePolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return PermissionService::can($user, 'evidence', 'view')
            || PermissionService::can($user, 'action_plans', 'view');
    }
}