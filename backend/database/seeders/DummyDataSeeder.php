<?php

namespace Database\Seeders;

use App\Models\Finding;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminSpi = User::where('email', 'admin_spi@example.com')->first();
        if (!$adminSpi) {
            return;
        }

        Finding::create([
            'code' => 'TL-2026-0001',
            'title' => 'Kelemahan Kontrol Akses Database',
            'finding_date' => now()->subMonths(3)->toDateString(),
            'severity' => 'high',
            'recommendation' => 'Implementasikan role-based access control (RBAC) dan audit logging',
            'auditor_action_plan' => 'Database access harus dibatasi per role dengan MFA',
            'status' => 'draft',
            'created_by' => $adminSpi->id,
            'is_active' => true,
        ]);
    }
}
