<?php

namespace App\Services;

use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Kerangka penjaga temuan CLOSED (R2). Aturan penuh (audit nilai lama & baru,
 * hanya kepala_spi) diselesaikan di R8.
 */
class ClosedFindingGuard
{
    public function assertEditable(Finding $finding, ?User $user = null): void
    {
        $user = $user ?? auth()->user();

        if ($finding->status !== FindingStatus::Closed) {
            return;
        }

        if ($user && ($user->role === Role::Kepala_spi || $user->role === Role::SuperAdmin)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Temuan Closed hanya dapat diubah oleh Kepala SPI.',
        ]);
    }
}
