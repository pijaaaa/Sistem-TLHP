<?php

use App\Enums\ActionPlanStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\ActionPlanDocument;
use App\Models\Department;
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
        'code' => 'T-M5-' . \Illuminate\Support\Str::random(4),
        'title' => 'Test temuan M5',
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

test('PIC bisa membuat draft rencana aksi', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Perbaiki proses kontrol',
        'weight' => 60,
    ])->assertStatus(201)
        ->assertJsonPath('data.status', ActionPlanStatus::Draft->value)
        ->assertJsonPath('data.weight', '60.00')
        ->assertJsonPath('data.title', 'Perbaiki proses kontrol');

    expect(ActionPlan::where('finding_department_id', $this->fd->id)->count())->toBe(1);
});

test('PIC tidak bisa membuat rencana aksi untuk finding_department yang bukan miliknya', function () {
    $pic2 = createTestUser(Role::StaffDept, 'EKS');

    $this->actingAs($pic2, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Test',
        'weight' => 100,
    ])->assertStatus(403);
});

test('submit ditolak 422 jika total bobot aktif bukan 100', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 60,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")
        ->assertStatus(422)
        ->assertJsonPath('errors.weight.0', 'Total bobot tindak lanjut aktif harus tepat 100%. Saat ini: 60.00%.');
});

test('PIC bisa submit rencana aksi ketika total bobot = 100', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::Submitted->value);
});

test('submit ditolak 422 jika status bukan draft/revisi', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")->assertOk();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit")
        ->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Hanya rencana aksi dalam status Draft atau Revisi yang dapat diajukan.');
});

test('submit memperhitungkan multiple action plans yang total bobotnya 100', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut A',
        'weight' => 40,
    ])->assertStatus(201);

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut B',
        'weight' => 60,
    ])->assertStatus(201);

    $ap1 = ActionPlan::where('finding_department_id', $this->fd->id)->orderBy('id')->first();
    $ap2 = ActionPlan::where('finding_department_id', $this->fd->id)->orderBy('id', 'desc')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap1->id}/submit")->assertOk();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/submit")->assertOk();

    expect(ActionPlan::find($ap1->id)->status)->toBe(ActionPlanStatus::Submitted);
    expect(ActionPlan::find($ap2->id)->status)->toBe(ActionPlanStatus::Submitted);
});

test('PIC bisa upload dokumen berlabel', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/documents", [
        'document' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
        'label' => 'Evidence awal',
    ])->assertStatus(201)
        ->assertJsonPath('data.label', 'Evidence awal')
        ->assertJsonPath('data.name', 'evidence.pdf');

    expect(ActionPlanDocument::where('action_plan_id', $ap->id)->count())->toBe(1);
});

test('upload dokumen ditolak 422 untuk tipe file tidak diizinkan', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/documents", [
        'document' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        'label' => 'Test',
    ])->assertStatus(422);
});

test('Manager Dept bisa melihat dan mengupdate rencana aksi departemennya', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->managerDept, 'sanctum')->getJson("/api/v1/action-plans/{$ap->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Tindak lanjut');

    $this->actingAs($this->managerDept, 'sanctum')->putJson("/api/v1/action-plans/{$ap->id}", [
        'title' => 'Updated by Manager',
    ])->assertOk()
        ->assertJsonPath('data.title', 'Updated by Manager');
});

test('Manager Dept tidak bisa melihat rencana aksi departemen lain', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();
    $managerEks = createTestUser(Role::ManagerDept, 'EKS');

    $this->actingAs($managerEks, 'sanctum')->getJson("/api/v1/action-plans/{$ap->id}")->assertStatus(403);
});

test('staff tidak boleh akses action_plans create via direct route (405)', function () {
    $managerIa2 = createTestUser(Role::ManagerIa, 'IA');

    $this->actingAs($managerIa2, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_department_id' => $this->fd->id,
        'title' => 'test',
        'weight' => 100,
    ])->assertStatus(405);
});

test('dokumen rencana aksi bisa diunduh', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/documents", [
        'document' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
        'label' => 'Evidence',
    ])->assertStatus(201);

    $doc = ActionPlanDocument::where('action_plan_id', $ap->id)->first();
    $this->actingAs($this->pic, 'sanctum')->getJson("/api/v1/action-plan-documents/{$doc->id}/download")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('manager dept bisa menghapus rencana aksi', function () {
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/finding-departments/{$this->fd->id}/action-plans", [
        'title' => 'Tindak lanjut',
        'weight' => 100,
    ])->assertStatus(201);

    $ap = ActionPlan::where('finding_department_id', $this->fd->id)->latest('id')->first();

    $this->actingAs($this->pic, 'sanctum')->deleteJson("/api/v1/action-plans/{$ap->id}")->assertOk();
    expect(ActionPlan::find($ap->id))->toBeNull();
});
