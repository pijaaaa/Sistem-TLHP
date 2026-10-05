<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingDepartmentStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FindingDepartment;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $this->admin = createTestUser(Role::AdminSpi);
    $this->managerIa = createTestUser(Role::ManagerIa, 'IA');
    $this->managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $this->pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');

    $this->findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M6-' . \Illuminate\Support\Str::random(4),
        'title' => 'Test temuan M6',
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

function createDraftPlan($pic, $fdId, float $weight = 100, string $title = 'Tindak lanjut'): ActionPlan
{
    test()->actingAs($pic, 'sanctum')->postJson("/api/v1/finding-departments/{$fdId}/action-plans", [
        'title' => $title,
        'weight' => $weight,
    ])->assertStatus(201);

    return ActionPlan::where('finding_department_id', $fdId)->latest('id')->first();
}

function createSubmittedPlan($testCase, $pic, $fdId, float $weight = 100, string $title = 'Tindak lanjut'): ActionPlan
{
    $ap = createDraftPlan($pic, $fdId, $weight, $title);
    $testCase->actingAs($pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")->assertOk();

    return $ap->fresh();
}

test('manager dept bisa menyetujui rencana aksi yang diajukan', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);

    $res = $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/approve");

    $res->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::WaitingEvidence->value)
        ->assertJsonPath('data.approved_by', $this->managerDept->id);

    expect($this->fd->fresh()->status)->toBe(FindingDepartmentStatus::InProgress);
});

test('approve ditolak 422 jika total bobot bukan 100', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut A',
        'weight' => 60,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")
        ->assertStatus(422);
});

test('approve ditolak 422 jika status bukan diajukan', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/approve")
        ->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Hanya rencana aksi dalam status Diajukan yang dapat disetujui.');
});

test('PIC tidak bisa menyetujui rencana aksi (403)', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/approve")
        ->assertStatus(403);
});

test('manager dept bisa menolak rencana aksi dengan alasan', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);

    $res = $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/reject", [
        'reason' => 'Rencana aksi tidak sesuai temuan',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::Rejected->value)
        ->assertJsonPath('data.rejection_reason', 'Rencana aksi tidak sesuai temuan');
});

test('reject ditolak 422 jika alasan kosong', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/reject", [
        'reason' => '',
    ])->assertStatus(422)
        ->assertJsonPath('errors.reason.0', 'Alasan penolakan wajib diisi.');
});

test('manager dept bisa meminta revisi rencana aksi', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);

    $res = $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/revision", [
        'reason' => 'Tambahkan detail dokumentasi',
    ]);

    $res->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::Revision->value);
});

test('PIC bisa mengajukan ulang rencana aksi setelah revisi', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/revision", [
        'reason' => 'Perlu revisi',
    ])->assertOk();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::Submitted->value);
});

test('manager dept bisa override bobot rencana aksi', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 60,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $res = $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/override-weight", [
        'weight' => 100,
    ]);

    $res->assertOk()
        ->assertJsonPath('data.weight', '100.00');
});

test('PIC tidak bisa override bobot rencana aksi milik PIC lain', function () {
    $ap = createSubmittedPlan($this, $this->pic, $this->fd->id, 100);
    $pic2 = createTestUser(Role::StaffDept, 'FINANCE_ICT');

    $this->actingAs($pic2, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/override-weight", [
        'weight' => 50,
    ])->assertStatus(403);
});

test('rencana aksi yang ditolak tidak dihitung dalam total bobot aktif', function () {
    $ap1 = createDraftPlan($this->pic, $this->fd->id, 60, 'Tindak lanjut A');
    $ap2 = createDraftPlan($this->pic, $this->fd->id, 40, 'Tindak lanjut B');

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap1->id}/submit")->assertOk();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/submit")->assertOk();

    $this->actingAs($this->managerDept, 'sanctum')->postJson("/api/v1/action-plans/{$ap1->id}/reject", [
        'reason' => 'Tidak sesuai',
    ])->assertOk();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut C',
        'weight' => 60,
    ])->assertStatus(201);

    $ap3 = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap3->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::Submitted->value);
});
