<?php

use App\Enums\AssessmentStatus;
use App\Enums\AuditorConclusion;
use App\Enums\FindingDepartmentStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Models\FindingVerification;
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
    $this->managerSpi = createTestUser(Role::ManagerSpi);
});

/**
 * Jalankan alur penuh lalu assessment. $assessment menentukan status IA.
 * Mengembalikan [findingId].
 */
function runCycleToAssessment($testCase, array $ctx, string $assessment = 'ssr'): array
{
    $findingId = $testCase->actingAs($ctx['admin'], 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M9-' . \Illuminate\Support\Str::random(4),
        'title' => 'Test temuan M9',
    ])->json('data.id');

    $testCase->actingAs($ctx['admin'], 'sanctum')->postJson("/api/v1/findings/{$findingId}/send-to-ia")->assertOk();

    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $testCase->actingAs($ctx['managerIa'], 'sanctum')->postJson("/api/v1/findings/{$findingId}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertOk();

    $fd = FindingDepartment::where('finding_id', $findingId)->where('department_id', $dept->id)->first();
    $testCase->actingAs($ctx['managerDept'], 'sanctum')
        ->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", ['pic_ids' => [$ctx['pic']->id]])
        ->assertOk();

    $apId = $testCase->actingAs($ctx['pic'], 'sanctum')
        ->postJson("/api/v1/finding-departments/{$fd->id}/action-plans", [
            'title' => 'Tindak lanjut',
            'weight' => 100,
        ])->json('data.id');

    $testCase->actingAs($ctx['pic'], 'sanctum')->postJson("/api/v1/action-plans/{$apId}/submit")->assertOk();
    $testCase->actingAs($ctx['managerDept'], 'sanctum')->postJson("/api/v1/action-plans/{$apId}/approve")->assertOk();

    $testCase->actingAs($ctx['pic'], 'sanctum')
        ->post("/api/v1/action-plans/{$apId}/evidence", [
            'files' => [['file' => UploadedFile::fake()->create('ev.pdf', 100, 'application/pdf')]],
        ])->assertStatus(201);

    $testCase->actingAs($ctx['managerDept'], 'sanctum')
        ->postJson("/api/v1/action-plans/{$apId}/evidence/approve")->assertOk();

    $testCase->actingAs($ctx['managerDept'], 'sanctum')
        ->postJson("/api/v1/finding-departments/{$fd->id}/forward-to-ia")->assertOk();

    $testCase->actingAs($ctx['managerIa'], 'sanctum')
        ->postJson("/api/v1/findings/{$findingId}/assess", [
            'assessment_status' => $assessment,
            'note' => 'Catatan assessment',
        ])->assertOk();

    return [$findingId, $fd->id];
}

function ctx($testCase)
{
    return [
        'admin' => $testCase->admin,
        'managerIa' => $testCase->managerIa,
        'managerDept' => $testCase->managerDept,
        'pic' => $testCase->pic,
    ];
}

test('manager spi melihat temuan yang menunggu verifikasi', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this));

    expect(Finding::find($findingId)->status)->toBe(FindingStatus::PendingVerificationSpi);

    $res = $this->actingAs($this->managerSpi, 'sanctum')->getJson('/api/v1/verifications');
    $res->assertOk();

    $ids = collect($res->json('data.data'))->pluck('id');
    expect($ids)->toContain($findingId);
});

test('manager spi menutup temuan dengan hasil auditor unsatisfactory', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this));

    $res = $this->actingAs($this->managerSpi, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::Closed->value,
        'auditor_result' => 'Sudah sesuai rekomendasi',
        'verified_date' => '2026-10-06',
        'notes' => 'Tidak ada temuan lanjutan',
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('data.verification.is_closed', true)
        ->assertJsonPath('data.verification.auditor_conclusion', 'ditutup');

    expect(Finding::find($findingId)->status)->toBe(FindingStatus::Closed);
    expect(FindingVerification::where('finding_id', $findingId)->count())->toBe(1);
});

test('closing ditolak bila assessment sebelumnya bukan SSR', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this), AssessmentStatus::BelumDitindaklanjuti->value);

    // Paksa ke verifikasi SPI tanpa assessment SSR.
    Finding::find($findingId)->update([
        'status' => FindingStatus::PendingVerificationSpi->value,
    ]);

    $this->actingAs($this->managerSpi, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::Closed->value,
    ])->assertStatus(422)
        ->assertJsonPath('errors.assessment.0', 'Temuan hanya dapat ditutup bila assessment IA sebelumnya berstatus SSR.');
});

test('perlu perbaikan mengembalikan temuan ke IA dan memulai ronde baru', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this));

    $oldCode = Finding::find($findingId)->code;

    $res = $this->actingAs($this->managerSpi, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::NeedsRevision->value,
        'auditor_result' => 'Masih ada temuan',
        'notes' => 'Dokumentasi belum memadai',
    ]);

    $res->assertStatus(201)
        ->assertJsonPath('data.verification.is_closed', false);

    $finding = Finding::find($findingId);
    expect($finding->status)->toBe(FindingStatus::Distributed);
    expect($finding->current_round)->toBe(2);
    expect($finding->code)->toBe($oldCode . '-R2');
    expect($finding->assessment_status)->toBe(AssessmentStatus::Bsr);

    expect(FindingDepartment::where('finding_id', $findingId)->where('round', 2)->count())->toBe(1);
});

test('perlu perbaikan ditolak 422 jika catatan kosong', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this));

    $this->actingAs($this->managerSpi, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::NeedsRevision->value,
        'notes' => '',
    ])->assertStatus(422)
        ->assertJsonPath('errors.notes.0', 'Catatan wajib diisi saat mengembalikan temuan ke Manager IA.');
});

test('verifikasi ditolak 422 bila temuan tidak menunggu verifikasi SPI', function () {
    $findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M9-X',
        'title' => 'Belum siap',
    ])->json('data.id');

    $this->actingAs($this->managerSpi, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::Closed->value,
    ])->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Hanya temuan dalam status Menunggu Verifikasi SPI yang dapat diverifikasi.');
});

test('manager IA tidak bisa mencatat verifikasi (403)', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this));

    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::Closed->value,
    ])->assertStatus(403);
});

test('riwayat verifikasi tersimpan per ronde', function () {
    [$findingId] = runCycleToAssessment($this, ctx($this));

    $this->actingAs($this->managerSpi, 'sanctum')->postJson("/api/v1/findings/{$findingId}/verifications", [
        'auditor_conclusion' => AuditorConclusion::NeedsRevision->value,
        'notes' => 'Perlu revisi',
    ])->assertStatus(201);

    $res = $this->actingAs($this->managerSpi, 'sanctum')->getJson("/api/v1/findings/{$findingId}/verifications");
    $res->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.round', 1);

    expect(FindingVerification::where('finding_id', $findingId)->first()->round)->toBe(1);
});