<?php

use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
});

test('manager ia bisa melihat daftar temuan yang perlu didistribusikan', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-001', 'title' => 'Temuan distribusi',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $response = $this->actingAs($managerIa, 'sanctum')->getJson('/api/v1/findings/distribution');
    $response->assertOk();
    $codes = collect($response->json('data.data'))->pluck('code');
    expect($codes)->toContain('T-M4-001');
});

test('manager ia mengirim 403 jika tidak punya permission findings.distribution view', function () {
    $staff = createTestUser(Role::StaffDept);

    $this->actingAs($staff, 'sanctum')->getJson('/api/v1/findings/distribution')
        ->assertStatus(403);
});

test('manager ia bisa mendistribusikan temuan ke multiple departemen', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-002', 'title' => 'Test distribusi multi',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $dept1 = Department::where('code', 'FINANCE_ICT')->first();
    $dept2 = Department::where('code', 'EKS')->first();

    $response = $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$dept1->id, $dept2->id],
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Temuan berhasil didistribusikan ke departemen.');

    expect(Finding::find($id)->status)->toBe(FindingStatus::Distributed);
    expect(FindingDepartment::where('finding_id', $id)->count())->toBe(2);

    $fds = FindingDepartment::where('finding_id', $id)->get();
    expect($fds->every(fn ($fd) => $fd->status === \App\Enums\FindingDepartmentStatus::Received))->toBeTrue();
});

test('distribusi ditolak 422 jika temuan tidak dalam status dikirim_ke_ia', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-003', 'title' => 'Test tidak kirim',
    ])->json('data.id');

    $dept = Department::where('code', 'FINANCE_ICT')->first();

    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertStatus(422)
      ->assertJsonPath('errors.status.0', 'Hanya temuan dalam status Dikirim ke IA yang dapat didistribusikan.');
});

test('distribusi ditolak 422 jika department_ids kosong', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-004', 'title' => 'Test empty',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [],
    ])->assertStatus(422);
});

test('staff tidak boleh mendistribusikan (403)', function () {
    $admin = createTestUser(Role::AdminSpi);
    $staff = createTestUser(Role::StaffDept);

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-005', 'title' => 'Test',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $dept = Department::where('code', 'FINANCE_ICT')->first();

    $this->actingAs($staff, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertStatus(403);
});

test('manager dept bisa assign PIC dari departemennya', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');
    $managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-006', 'title' => 'Test PIC assignment',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertOk();

    $fd = FindingDepartment::where('finding_id', $id)->where('department_id', $dept->id)->first();

    $response = $this->actingAs($managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", [
        'pic_ids' => [$pic->id],
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'PIC berhasil ditugaskan.');

    expect(FindingDepartment::find($fd->id)->status)->toBe(\App\Enums\FindingDepartmentStatus::PicAssigned);
    expect($fd->fresh()->pics->pluck('id')->toArray())->toContain($pic->id);
});

test('assign pics ditolak 422 jika status bukan DITERIMA', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');
    $managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-007', 'title' => 'Test already assigned',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertOk();

    $fd = FindingDepartment::where('finding_id', $id)->where('department_id', $dept->id)->first();

    $this->actingAs($managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", [
        'pic_ids' => [$pic->id],
    ])->assertOk();

    $this->actingAs($managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", [
        'pic_ids' => [$pic->id],
    ])->assertStatus(422)
      ->assertJsonPath('errors.status.0', 'Hanya departemen dengan status Diterima yang dapat ditugaskan PIC.');
});

test('manager dept tidak boleh assign PIC dari departemen lain', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');
    $managerDeptFinance = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $picEks = createTestUser(Role::StaffDept, 'EKS');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-008', 'title' => 'Test cross dept PIC',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $deptFinance = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$deptFinance->id],
    ])->assertOk();

    $fd = FindingDepartment::where('finding_id', $id)->where('department_id', $deptFinance->id)->first();

    $this->actingAs($managerDeptFinance, 'sanctum')->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", [
        'pic_ids' => [$picEks->id],
    ])->assertStatus(422)
      ->assertJsonPath('errors.pic_ids.0', 'Semua PIC harus dari departemen yang sama dengan temuan departemen ini.');
});

test('PIC (staff_dept) bisa melihat finding_departments yang dialokasikan padanya', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');
    $managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-009', 'title' => 'Test PIC visibility',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$dept->id],
    ])->assertOk();

    $fd = FindingDepartment::where('finding_id', $id)->where('department_id', $dept->id)->first();

    $this->actingAs($managerDept, 'sanctum')->postJson("/api/v1/finding-departments/{$fd->id}/assign-pics", [
        'pic_ids' => [$pic->id],
    ])->assertOk();

    $response = $this->actingAs($pic, 'sanctum')->getJson('/api/v1/finding-departments');
    $response->assertOk();

    $foundIds = collect($response->json('data.data'))->pluck('id');
    expect($foundIds)->toContain($fd->id);
});

test('manager dept tidak bisa melihat finding_departments departemen lain', function () {
    $admin = createTestUser(Role::AdminSpi);
    $managerIa = createTestUser(Role::ManagerIa, 'IA');
    $managerFinance = createTestUser(Role::ManagerDept, 'FINANCE_ICT');

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M4-010', 'title' => 'Test dept scope',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $deptFinance = Department::where('code', 'FINANCE_ICT')->first();
    $deptEks = Department::where('code', 'EKS')->first();
    $this->actingAs($managerIa, 'sanctum')->postJson("/api/v1/findings/{$id}/distribute", [
        'department_ids' => [$deptFinance->id, $deptEks->id],
    ])->assertOk();

    $response = $this->actingAs($managerFinance, 'sanctum')->getJson('/api/v1/finding-departments');
    $response->assertOk();

    $departMents = collect($response->json('data.data'));
    $deptIds = $departMents->pluck('department_id');
    expect($deptIds)->toContain($deptFinance->id);
    expect($deptIds)->not->toContain($deptEks->id);
});
