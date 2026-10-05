<?php

use App\Enums\AssessmentStatus;
use App\Enums\ActionPlanStatus;
use App\Enums\FindingDepartmentStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingDepartment;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    Storage::fake(config('upload.disk'));

    $this->admin = createTestUser(Role::AdminSpi);
    $this->managerIa = createTestUser(Role::ManagerIa, 'IA');
    $this->managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $this->pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');
});

/**
 * Jalankan alur penuh sampai temuan menunggu assessment IA.
 * Mengembalikan [findingId, findingDepartmentId].
 */
function runFullCycleToAssessment($testCase, array $ctx, array $deptCodes = ['FINANCE_ICT']): array
{
    $findingId = $testCase->actingAs($ctx['admin'], 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M8-' . \Illuminate\Support\Str::random(4),
        'title' => 'Test temuan M8',
    ])->json('data.id');

    $testCase->actingAs($ctx['admin'], 'sanctum')->postJson("/api/v1/findings/{$findingId}/send-to-ia")->assertOk();

    $deptIds = Department::whereIn('code', $deptCodes)->pluck('id')->all();
    $testCase->actingAs($ctx['managerIa'], 'sanctum')->postJson("/api/v1/findings/{$findingId}/distribute", [
        'department_ids' => $deptIds,
    ])->assertOk();

    $fds = FindingDepartment::where('finding_id', $findingId)->get();

    foreach ($fds as $fd) {
        $deptCode = Department::find($fd->department_id)->code;
        $managerDept = $ctx['managerDept']->department_id === $fd->department_id
            ? $ctx['managerDept']
            : createTestUser(Role::ManagerDept, $deptCode);
        $pic = $ctx['pic']->department_id === $fd->department_id
            ? $ctx['pic']
            : createTestUser(Role::StaffDept, $deptCode);

        $testCase->actingAs($managerDept, 'sanctum')
            ->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", ['pic_ids' => [$pic->id]])
            ->assertOk();

        $apId = $testCase->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/finding-departments/{$fd->id}/action-plans", [
                'title' => 'Tindak lanjut',
                'weight' => 100,
            ])->json('data.id');

        $testCase->actingAs($pic, 'sanctum')->postJson("/api/v1/action-plans/{$apId}/submit")->assertOk();
        $testCase->actingAs($managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$apId}/approve")->assertOk();

        $testCase->actingAs($pic, 'sanctum')
            ->post("/api/v1/action-plans/{$apId}/evidence", [
                'files' => [
                    ['file' => UploadedFile::fake()->create('ev.pdf', 100, 'application/pdf')],
                ],
            ])->assertStatus(201);

        $testCase->actingAs($managerDept, 'sanctum')
            ->postJson("/api/v1/action-plans/{$apId}/evidence/approve")
            ->assertOk();

        $testCase->actingAs($managerDept, 'sanctum')
            ->postJson("/api/v1/finding-departments/{$fd->id}/forward-to-ia")
            ->assertOk();
    }

    return [$findingId, $fds->first()->id];
}

test('manager ia melihat daftar temuan yang menunggu assessment', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $res = $this->actingAs($this->managerIa, 'sanctum')->getJson('/api/v1/assessments');
    $res->assertOk();

    $ids = collect($res->json('data.data'))->pluck('id');
    expect($ids)->toContain($findingId);
});

test('assessment ditolak 422 bila temuan belum menunggu assessment', function () {
    $findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M8-X1',
        'title' => 'Belum siap',
    ])->json('data.id');

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Ssr->value,
    ])->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Hanya temuan dalam status Menunggu Assessment IA yang dapat di-assess.');
});

test('assessment ditolak 422 bila belum semua departemen meneruskan', function () {
    $findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M8-X2',
        'title' => 'Belum semua dept',
    ])->json('data.id');

    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/findings/{$findingId}/send-to-ia")->assertOk();

    $depts = Department::whereIn('code', ['FINANCE_ICT', 'EKS'])->pluck('id')->all();
    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/distribute", [
        'department_ids' => $depts,
    ])->assertOk();

    // Paksa status menjadi menunggu assessment tanpa semua dept meneruskan.
    Finding::find($findingId)->update(['status' => FindingStatus::PendingIaAssessment->value]);

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Ssr->value,
    ])->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Assessment hanya dapat dilakukan setelah semua departemen meneruskan temuan ke IA.');
});

test('SSR memindahkan temuan ke menunggu verifikasi SPI', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $res = $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Ssr->value,
        'note' => 'Sudah satisfactory',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.status', FindingStatus::PendingVerificationSpi->value)
        ->assertJsonPath('data.assessment_status', AssessmentStatus::Ssr->value)
        ->assertJsonPath('data.assessed_by', $this->managerIa->id);

    expect(Finding::find($findingId)->current_round)->toBe(1);
});

test('Tidak Dapat Ditindaklanjuti menutup kasus dan mewajibkan alasan', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::TidakDapatDitindaklanjuti->value,
    ])->assertStatus(422)
        ->assertJsonPath('errors.note.0', 'Alasan wajib diisi untuk status Tidak Dapat Ditindaklanjuti.');

    $res = $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::TidakDapatDitindaklanjuti->value,
        'note' => 'Di luar kendali perusahaan',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.status', FindingStatus::CaseClosed->value);

    expect(Finding::find($findingId)->assessment_note)->toBe('Di luar kendali perusahaan');
});

test('Belum Ditindaklanjuti tidak memicu ronde baru dan tetap menunggu assessment', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $res = $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::BelumDitindaklanjuti->value,
    ]);

    $res->assertOk()
        ->assertJsonPath('data.status', FindingStatus::PendingIaAssessment->value)
        ->assertJsonPath('data.current_round', 1);
});

test('BSR memulai ronde baru dan mengubah kode temuan', function () {
    [$findingId, $fdId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $oldCode = Finding::find($findingId)->code;

    $res = $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Bsr->value,
        'note' => 'Perlu perbaikan lanjutan',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.status', FindingStatus::Distributed->value)
        ->assertJsonPath('data.current_round', 2);

    $finding = Finding::find($findingId);
    expect($finding->code)->toBe($oldCode . '-R2');
    expect($finding->code)->not->toBe($oldCode);
});

test('BSR membawa departemen dari ronde sebelumnya ke ronde baru', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ], ['FINANCE_ICT', 'EKS']);

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Bsr->value,
    ])->assertOk();

    $round2 = FindingDepartment::where('finding_id', $findingId)->where('round', 2)->get();
    expect($round2)->toHaveCount(2);
    expect($round2->every(fn ($fd) => $fd->status === FindingDepartmentStatus::Received))->toBeTrue();

    $round1 = FindingDepartment::where('finding_id', $findingId)->where('round', 1)->get();
    expect($round1)->toHaveCount(2);
    expect($round1->every(fn ($fd) => $fd->status === FindingDepartmentStatus::ForwardedToIa))->toBeTrue();
});

test('manager ia boleh mengubah daftar departemen saat BSR', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ], ['FINANCE_ICT', 'EKS']);

    $eks = Department::where('code', 'EKS')->first();
    $finance = Department::where('code', 'FINANCE_ICT')->first();

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Bsr->value,
        'department_ids' => [$finance->id],
    ])->assertOk();

    $round2 = FindingDepartment::where('finding_id', $findingId)->where('round', 2)->get();
    expect($round2)->toHaveCount(1);
    expect($round2->first()->department_id)->toBe($finance->id);
});

test('PIC tidak bisa melakukan assessment (403)', function () {
    [$findingId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Ssr->value,
    ])->assertStatus(403);
});

test('riwayat ronde lama tetap dapat dibaca setelah ronde baru dimulai', function () {
    [$findingId, $fdId] = runFullCycleToAssessment($this, [
        'admin' => $this->admin,
        'managerIa' => $this->managerIa,
        'managerDept' => $this->managerDept,
        'pic' => $this->pic,
    ]);

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/assess", [
        'assessment_status' => AssessmentStatus::Bsr->value,
    ])->assertOk();

    $oldFd = FindingDepartment::find($fdId);
    expect($oldFd->round)->toBe(1);
    expect($oldFd->status)->toBe(FindingDepartmentStatus::ForwardedToIa);

    $oldPlan = ActionPlan::where('finding_department_id', $fdId)->first();
    expect($oldPlan->round)->toBe(1);
    expect($oldPlan->status)->toBe(ActionPlanStatus::EvidenceApproved);
});