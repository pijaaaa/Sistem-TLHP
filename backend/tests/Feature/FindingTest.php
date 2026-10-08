<?php

use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Finding;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class, \Database\Seeders\EmployeeUserSeeder::class]);
    Storage::fake(config('upload.disk'));
});

test('admin bisa membuat temuan dengan status draft', function () {
    $admin = createTestUser(Role::AdminSpi, 'SPI');

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Kelemahan kontrol procurement',
        'source' => 'BPK',
        'finding_date' => '2026-01-15',
        'scope' => 'Pengadaan',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', FindingStatus::Draft->value)
        ->assertJsonPath('data.status_label', FindingStatus::Draft->label());

    expect(Finding::where('title', 'Kelemahan kontrol procurement')->exists())->toBeTrue();
});

test('temuan wajib judul', function () {
    $admin = createTestUser(Role::AdminSpi, 'SPI');

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [])->assertStatus(422);
});

test('staff tidak boleh membuat temuan (403)', function () {
    $staff = createTestUser(Role::StaffDept);

    $this->actingAs($staff, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Tidak boleh',
    ])->assertStatus(403);
});

test('daftar temuan menampilkan data terbaru setelah create', function () {
    $admin = createTestUser(Role::AdminSpi, 'SPI');

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Cache check',
    ])->assertStatus(201);

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/findings');
    $response->assertOk();

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Cache check');
});

test('dokumen temuan wajib berlabel', function () {
    $admin = User::where('username', 'admin_spi')->first();

    $findingId = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Butuh dokumen',
    ])->assertStatus(201)->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$findingId}/documents", [
        'document' => UploadedFile::fake()->create('lhp.pdf', 100, 'application/pdf'),
    ])->assertStatus(422);
});
