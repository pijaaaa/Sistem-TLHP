<?php

namespace Database\Seeders;

use App\Enums\FindingStatus;
use App\Models\Finding;
use App\Models\Department;
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

        $financeIct = Department::where('code', 'FINANCE_ICT')->first();
        if (!$financeIct) {
            return;
        }

        Finding::create([
            'title' => 'Kelemahan Kontrol Akses Database',
            'finding_date' => now()->subMonths(3)->toDateString(),
            'response_period_start' => now()->subMonths(2)->toDateString(),
            'response_period_end' => now()->addMonths(1)->toDateString(),
            'lhp_number' => 'BPK/DIY/2026/01',
            'lhp_date' => now()->subMonths(3)->toDateString(),
            'source' => 'BPK',
            'scope' => 'Audit atas pengendalian akses sistem database perusahaan',
            'status' => FindingStatus::Draft->value,
            'fiscal_year' => now()->year,
            'created_by' => $adminSpi->id,
        ]);
    }
}
