<?php

use App\Enums\ActionPlanStatus;
use App\Enums\EvidenceStatus;
use App\Enums\FindingDepartmentStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\EvidenceSubmission;
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

    $this->findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M7-' . \Illuminate\Support\Str::random(4),
        'title' => 'Test temuan M7',
    ])->json('data.id');

    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/findings/{$this->findingId}/send-to-ia")->assertOk();

    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$this->findingId}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertOk();

    $this->fd = FindingDepartment::where('finding_id', $this->findingId)->where('department_id', $dept->id)->first();
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/assign-pics", [
        'pic_ids' => [$this->pic->id],
    ])->assertOk();
});

function makeDraft($pic, $fdId, float $weight, string $title): ActionPlan
{
    test()->actingAs($pic, 'sanctum')->postJson("/api/v1/finding-departments/{$fdId}/action-plans", [
        'title' => $title,
        'weight' => $weight,
    ])->assertStatus(201);

    return ActionPlan::where('finding_department_id', $fdId)->latest('id')->first();
}

function evidenceFormData(int $count = 2): array
{
    $files = [];
    for ($i = 0; $i < $count; $i++) {
        $files[] = [
            'file' => UploadedFile::fake()->create("evidence{$i}.pdf", 100, 'application/pdf'),
            'label' => "Label {$i}",
        ];
    }
    return ['files' => $files];
}

function submitEvidence($user, int $apId, int $count = 1)
{
    return test()->actingAs($user, 'sanctum')->post(
        "/api/v1/action-plans/{$apId}/evidence",
        evidenceFormData($count),
    );
}

function approveActionPlan($managerDept, ActionPlan $ap): ActionPlan
{
    test()->actingAs($managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/approve")->assertOk();

    return $ap->fresh();
}

function planAwaitingEvidence($managerDept, $pic, $fdId, float $weight = 100, string $title = 'Tindak lanjut'): ActionPlan
{
    $ap = makeDraft($pic, $fdId, $weight, $title);
    test()->actingAs($pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")->assertOk();

    return approveActionPlan($managerDept, $ap);
}

test('approve memindahkan rencana aksi ke status menunggu evidence', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id);

    expect($ap->status)->toBe(ActionPlanStatus::WaitingEvidence);
});

test('PIC bisa mengajukan evidence dengan banyak file berlabel', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id);

    $res = submitEvidence($this->pic, $ap->id, 2);

    $res->assertStatus(201)
        ->assertJsonPath('data.status', EvidenceStatus::Diajukan->value)
        ->assertJsonCount(2, 'data.files');

    expect(ActionPlan::find($ap->id)->status)->toBe(ActionPlanStatus::EvidenceSubmitted);
    expect(EvidenceSubmission::where('action_plan_id', $ap->id)->count())->toBe(1);
    expect(EvidenceSubmission::find(EvidenceSubmission::where('action_plan_id', $ap->id)->first()->id)->files()->count())->toBe(2);
});

test('submit evidence ditolak 422 jika tidak ada file', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id);

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence", [
        'files' => [],
    ])->assertStatus(422);
});

test('submit evidence ditolak 422 jika tipe file tidak diizinkan', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id);

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence", [
        'files' => [
            ['file' => UploadedFile::fake()->create('bad.exe', 100, 'application/octet-stream')],
        ],
    ])->assertStatus(422);
});

test('submit evidence ditolak 422 jika status belum disetujui', function () {
    $ap = makeDraft($this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Evidence hanya dapat diajukan untuk rencana aksi yang disetujui atau diminta revisi.');
});

test('manager dept bisa menyetujui evidence dan progress bertambah', function () {
    $ap = makeDraft($this->pic, $this->fd->id, 60, 'Tindak lanjut A');
    $ap2 = makeDraft($this->pic, $this->fd->id, 40, 'Tindak lanjut B');

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")->assertOk();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/submit")->assertOk();
    $ap = approveActionPlan($this->managerDept, $ap);
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/approve")->assertOk();

    submitEvidence($this->pic, $ap->id)->assertStatus(201);

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/approve")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::EvidenceApproved->value);

    $res = $this->actingAs($this->managerDept, 'sanctum')->getJson("/api/v1/finding-departments/{$this->fd->id}/progress");
    $res->assertOk()->assertJsonPath('data.progress', 60);
});

test('departemen menjadi selesai 100% saat semua evidence disetujui', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(201);

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/approve")->assertOk();

    expect($this->fd->fresh()->status)->toBe(FindingDepartmentStatus::Complete100);
});

test('manager dept bisa meminta revisi evidence lalu PIC mengajukan ulang', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(201);

    $res = $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/revision", [
        'note' => 'Dokumen kurang lengkap',
    ]);
    $res->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::EvidenceRevision->value);

    $res = submitEvidence($this->pic, $ap->id);
    $res->assertStatus(201);

    expect(EvidenceSubmission::where('action_plan_id', $ap->id)->count())->toBe(2);
});

test('request revisi evidence ditolak 422 jika catatan kosong', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(201);

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/revision", [
        'note' => '',
    ])->assertStatus(422)
        ->assertJsonPath('errors.note.0', 'Catatan revisi wajib diisi.');
});

test('PIC tidak bisa menyetujui evidence sendiri (403)', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(201);

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/approve")
        ->assertStatus(403);
});

test('teruskan ke IA ditolak 422 jika progress belum 100', function () {
    $ap = makeDraft($this->pic, $this->fd->id, 60, 'Tindak lanjut A');
    $ap2 = makeDraft($this->pic, $this->fd->id, 40, 'Tindak lanjut B');
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")->assertOk();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/submit")->assertOk();
    $ap = approveActionPlan($this->managerDept, $ap);
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/approve")->assertOk();

    submitEvidence($this->pic, $ap->id)->assertStatus(201);
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/approve")->assertOk();

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/forward-to-ia")
        ->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Hanya temuan departemen dengan status Selesai 100% yang dapat diteruskan ke IA.');
});

test('teruskan ke IA berhasil saat progress 100 dan temuan menunggu assessment IA', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(201);
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/approve")->assertOk();

    $res = $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/forward-to-ia");
    $res->assertOk()
        ->assertJsonPath('data.status', FindingDepartmentStatus::ForwardedToIa->value);

    expect(Finding::find($this->findingId)->status)->toBe(FindingStatus::PendingIaAssessment);
});

test('temuan tidak menunggu assessment IA sebelum semua departemen meneruskan', function () {
    $dept2 = Department::where('code', 'EKS')->first();
    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/findings/{$this->findingId}/distribute", [
        'department_ids' => [$dept2->id],
    ])->assertStatus(422);

    $fd2 = FindingDepartment::create([
        'finding_id' => $this->findingId,
        'department_id' => $dept2->id,
        'status' => FindingDepartmentStatus::Received,
    ]);

    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');
    submitEvidence($this->pic, $ap->id)->assertStatus(201);
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/evidence/approve")->assertOk();
    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/forward-to-ia")->assertOk();

    expect($fd2->fresh()->status)->toBe(FindingDepartmentStatus::Received);
    expect(Finding::find($this->findingId)->status)->not->toBe(FindingStatus::PendingIaAssessment);
});

test('evidence file bisa diunduh', function () {
    $ap = planAwaitingEvidence($this->managerDept, $this->pic, $this->fd->id, 100, 'Tindak lanjut');

    submitEvidence($this->pic, $ap->id)->assertStatus(201);

    $file = \App\Models\EvidenceFile::whereHas(
        'submission',
        fn ($q) => $q->where('action_plan_id', $ap->id),
    )->first();

    $this->actingAs($this->pic, 'sanctum')->getJson("/api/v1/evidence-files/{$file->id}/download")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
