<?php

namespace Database\Seeders;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

/**
 * Data demo lintas status alur e-TLHT. Menjalankan prasyarat: DepartmentSeeder,
 * MenuSeeder, PermissionSeeder, EmployeeUserSeeder, DummyDataSeeder (via DatabaseSeeder).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin_spi')->first();
        $finance = Department::where('code', 'FINANCE_ICT')->first();
        $eks = Department::where('code', 'EKS')->first();

        $makeFinding = function (array $data): Finding {
            $data += [
                'source' => 'BPK',
                'source_name' => null,
                'lhp_number' => 'LHP/' . now()->year . '/' . strtoupper(substr((string) mt_rand(), 0, 4)),
                'lhp_date' => now()->subMonths(3)->toDateString(),
                'finding_date' => now()->subMonths(3)->toDateString(),
                'response_period_start' => now()->subMonths(2)->toDateString(),
                'response_period_end' => now()->addMonths(2)->toDateString(),
                'scope' => 'Pengendalian proses bisnis',
                'created_by' => auth()->id(),
            ];

            return Finding::create($data);
        };

        // DRAFT
        $makeFinding(['title' => 'Demo — Temuan masih Draft', 'status' => FindingStatus::Draft, 'fiscal_year' => now()->year]);

        // TERDAFTAR
        $makeFinding(['title' => 'Demo — Temuan Terdaftar', 'status' => FindingStatus::Terdaftar, 'fiscal_year' => now()->year]);

        // PROSES: 1 AP + TL disetujui dengan progres.
        $process = $makeFinding(['title' => 'Demo — Proses Tindak Lanjut', 'status' => FindingStatus::ProsessTindakLanjut, 'fiscal_year' => now()->year]);
        if ($admin && $finance) {
            $ap = ActionPlan::create([
                'finding_id' => $process->id, 'department_id' => $finance->id,
                'code' => 'AP-DEMO-01', 'title' => 'Penguatan kontrol pengadaan', 'risk' => 'TINGGI',
                'loss_idr' => 250000000, 'deadline' => now()->addMonths(2)->toDateString(),
                'status' => ActionPlanStatus::ProsesTindakLanjut, 'sent_at' => now(), 'created_by' => $admin->id,
            ]);
            $pic = User::where('username', 'pic_1_finance_ict')->first();
            if ($pic) {
                $ap->assignees()->attach($pic->id, ['assigned_by' => $admin->id]);
                $fu = FollowUp::create([
                    'action_plan_id' => $ap->id, 'revision_no' => 0,
                    'description' => 'Menyusun dan menerapkan SOP verifikasi pembayaran',
                    'target_date' => now()->addWeek()->toDateString(), 'weight' => 100,
                    'progress' => 50, 'status' => FollowUpStatus::Disetujui,
                    'approved_by' => User::where('username', 'mgr_finance_ict')->first()?->id, 'approved_at' => now(),
                    'created_by' => $pic->id,
                ]);
                $fu->assignees()->attach($pic->id);
                $ap->update(['progress' => 50]);
            }
        }

        // MENUNGGU_STATUS_EKSTERNAL: AP SESUAI.
        $waiting = $makeFinding(['title' => 'Demo — Menunggu Status Eksternal', 'status' => FindingStatus::MenungguStatusEksternal, 'fiscal_year' => now()->year]);
        if ($admin && $eks) {
            ActionPlan::create([
                'finding_id' => $waiting->id, 'department_id' => $eks->id,
                'code' => 'AP-DEMO-02', 'title' => 'Perbaikan pengelolaan aset', 'risk' => 'SEDANG',
                'deadline' => now()->toDateString(), 'status' => ActionPlanStatus::Sesuai,
                'current_revision' => 0, 'progress' => 100, 'sent_at' => now(), 'created_by' => $admin->id,
            ]);
        }

        // CLOSED: SSR.
        $kepala = User::where('username', 'kepala_spi')->first();
        $closed = $makeFinding([
            'title' => 'Demo — Temuan Ditutup (SSR)',
            'status' => FindingStatus::Closed,
            'fiscal_year' => now()->year,
            'activated_at' => now()->subMonths(5),
            'closed_at' => now()->subDays(3),
            'closed_by' => $kepala?->id,
        ]);
        if ($admin && $finance) {
            ActionPlan::create([
                'finding_id' => $closed->id, 'department_id' => $finance->id,
                'code' => 'AP-DEMO-03', 'title' => 'Koreksi pencatatan piutang', 'risk' => 'RENDAH',
                'deadline' => now()->subDays(10)->toDateString(), 'status' => ActionPlanStatus::Closed,
                'current_revision' => 0, 'progress' => 100, 'sent_at' => now()->subMonths(4), 'created_by' => $admin->id,
            ]);
        }

        // Contoh notifikasi untuk manager.
        $manager = User::where('username', 'mgr_finance_ict')->first();
        if ($manager) {
            $manager->notify(new \App\Notifications\SubjectNotification('Demo: ada tindak lanjut menunggu persetujuan.', ['url' => '/persetujuan']));
            $manager->notify(new \App\Notifications\SubjectNotification('Demo: pengingat tenggat H-7.', []));
        }
    }
}