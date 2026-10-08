<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\FindingService;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
    $this->kepala = User::where('username', 'kepala_spi')->first();
});

function r10SeedReportingData(): array
{
    $finance = Department::where('code', 'FINANCE_ICT')->first();
    $eks = Department::where('code', 'EKS')->first();

    $create = function (Department $dept, string $title) {
        $svc = new FindingService();
        $finding = $svc->createDraft([
            'title' => $title,
            'source' => 'BPK',
            'source_name' => null,
            'lhp_number' => 'LHP/R10/' . $dept->code,
            'lhp_date' => '2026-06-01',
            'finding_date' => '2026-06-15',
            'response_period_start' => '2026-07-01',
            'response_period_end' => '2026-12-31',
            'scope' => 'Audit R10',
        ]);
        $finding->documents()->create(['label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1]);
        $svc->register($finding, [$dept->id]);
        $svc->activate($finding);

        $ap = ActionPlan::withoutGlobalScopes()->create([
            'finding_id' => $finding->id,
            'department_id' => $dept->id,
            'code' => 'AP-' . $dept->code . '-1',
            'title' => 'AP ' . $dept->code,
            'risk' => 'SEDANG',
            'loss_idr' => 5000000,
            'loss_usd' => 500,
            'status' => ActionPlanStatus::ProsesTindakLanjut,
            'sent_at' => now(),
            'created_by' => auth()->id(),
        ]);

        // Satu TL terlambat, satu belum selesai menunggu.
        FollowUp::withoutGlobalScopes()->create([
            'action_plan_id' => $ap->id,
            'revision_no' => 0,
            'description' => "TL terlambat {$dept->code}",
            'target_date' => '2026-09-01',
            'weight' => 60,
            'status' => FollowUpStatus::Disetujui,
        ]);
        FollowUp::withoutGlobalScopes()->create([
            'action_plan_id' => $ap->id,
            'revision_no' => 0,
            'description' => "TL aktif {$dept->code}",
            'target_date' => '2026-12-01',
            'weight' => 40,
            'status' => FollowUpStatus::Disetujui,
        ]);

        return $finding;
    };

    $f1 = $create($finance, 'Temuan Finance');
    $f2 = $create($eks, 'Temuan EKS');

    return [$f1, $f2];
}

test('rekap departemen mengikuti scope role', function () {
    [$f1, $f2] = r10SeedReportingData();

    // Manager hanya melihat departemennya.
    $res = $this->actingAs($this->manager, 'sanctum')->getJson('/api/v1/reports/departments');
    $res->assertOk();

    $depts = collect($res->json('data.data'))->pluck('department')->all();
    expect($depts)->toContain('FINANCE & ICT')
        ->and($depts)->not->toContain('EKS');

    // Admin (pemantau) melihat semua.
    $admin = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/reports/departments')
        ->assertOk()->json('data.data');

    expect(collect($admin)->pluck('department'))->toContain('EKS');
});

test('laporan keterlambatan hanya menampilkan data dalam scope', function () {
    r10SeedReportingData();

    $res = $this->actingAs($this->manager, 'sanctum')->getJson('/api/v1/reports/late');
    $res->assertOk();

    $descs = collect($res->json('data.data'))->pluck('description')->all();
    expect($descs)->toContain('TL terlambat FINANCE_ICT')
        ->and($descs)->not->toContain('TL terlambat EKS')
        ->and(collect($res->json('data.data'))->pluck('days_overdue')->first())->toBeGreaterThanOrEqual(1);
});

test('export laporan tidak membocorkan data di luar scope', function () {
    r10SeedReportingData();

    $managerContent = $this->actingAs($this->manager, 'sanctum')->get('/api/v1/exports/reports/departments')
        ->assertOk()->streamedContent();

    expect($managerContent)->toContain('FINANCE & ICT')
        ->and($managerContent)->not->toContain('EKS');

    $adminContent = $this->actingAs($this->admin, 'sanctum')->get('/api/v1/exports/reports/departments')
        ->assertOk()->streamedContent();

    expect($adminContent)->toContain('EKS');
});

test('tree temuan mengembalikan action plan dan cache dua request sama', function () {
    [$f1] = r10SeedReportingData();

    $res = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/findings/{$f1->id}/tree");
    $res->assertOk();
    expect(collect($res->json('data'))->pluck('code'))->toContain('AP-FINANCE_ICT-1');

    $again = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/findings/{$f1->id}/tree");
    expect($again->json('data'))->toEqual($res->json('data'));
});