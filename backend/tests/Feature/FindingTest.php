<?php

use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingDocument;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    Storage::fake(config('upload.disk'));
});

test('admin bisa membuat temuan dengan status draft', function () {
    $admin = createTestUser(Role::AdminSpi);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-001',
        'title' => 'Kelemahan kontrol procurement',
        'finding_date' => '2026-01-15',
        'severity' => 'high',
        'recommendation' => 'Perkuat proses persetujuan',
        'auditor_action_plan' => 'Menyusun SOP baru',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', FindingStatus::Draft->value)
        ->assertJsonPath('data.status_label', FindingStatus::Draft->label());

    expect(Finding::where('code', 'T-001')->exists())->toBeTrue();
});

test('kode temuan harus unik', function () {
    $admin = createTestUser(Role::AdminSpi);
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-002', 'title' => 'A',
    ])->assertStatus(201);

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-002', 'title' => 'B',
    ])->assertStatus(422);
});

test('staff tidak boleh membuat temuan (403)', function () {
    $staff = createTestUser(Role::StaffDept);

    $this->actingAs($staff, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-005', 'title' => 'Tidak boleh',
    ])->assertStatus(403);
});

test('daftar temuan ter-cache dan terinvalidasi saat create', function () {
    $admin = createTestUser(Role::AdminSpi);

    $this->actingAs($admin, 'sanctum')->getJson('/api/v1/findings')->assertOk();

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-008', 'title' => 'Cache check',
    ])->assertStatus(201);

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/findings');
    $response->assertOk();

    $codes = collect($response->json('data.data'))->pluck('code');
    expect($codes)->toContain('T-008');
});