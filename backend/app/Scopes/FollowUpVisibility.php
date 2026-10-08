<?php

namespace App\Scopes;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FollowUpVisibility implements Scope
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

        if (in_array($user->role->value, [Role::AdminSpi->value, Role::InternalAudit->value, Role::Kepala_spi->value], true)
            || $user->role->value === Role::ManagerIa->value
            || $user->role->value === Role::Direksi->value
        ) {
            // Pemantau hanya melihat tindak lanjut yang sudah disetujui manager.
            $query->whereIn('status', [
                'DISETUJUI',
                'MENUNGGU_PERSETUJUAN_SELESAI',
                'SELESAI',
            ]);
            return;
        }

        if ($user->role->value === Role::ManagerDept->value) {
            $query->whereHas('actionPlan', function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->whereNull('deleted_at');
            });
            return;
        }

        if ($user->role->value === Role::StaffDept->value) {
            $query->whereIn('id', function ($q) use ($user) {
                $q->select('follow_up_id')
                    ->from('follow_up_assignees')
                    ->where('user_id', $user->id);
            })
                ->whereHas('actionPlan', function ($q) use ($user) {
                    $q->whereNull('deleted_at')
                        ->whereHas('finding', function ($f) {
                            $f->where('status', '!=', 'CLOSED')->whereNull('deleted_at');
                        });
                });
            return;
        }

        $query->whereRaw('1=0');
    }
}