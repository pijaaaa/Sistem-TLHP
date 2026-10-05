<?php

namespace App\Policies;

use App\Models\ActionPlan;
use App\Models\ActionPlanDocument;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActionPlanDocumentPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool
    {
        return PermissionService::can($user, 'action_plans', 'view');
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'action_plans', 'create');
    }

    public function delete(User $user, ActionPlanDocument $document): bool
    {
        return PermissionService::can($user, 'action_plans', 'delete')
            || PermissionService::can($user, 'action_plans', 'update');
    }

    public function download(User $user, ActionPlanDocument $document): bool
    {
        return $this->view($user);
    }
}
