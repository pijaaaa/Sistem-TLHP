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

test('temuan dikirim ke ia berpindah status draft menjadi dikirim_ke_ia', function () {
    $admin = createTestUser(Role::AdminSpi);

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-003', 'title' => 'Temuan uji',
    ])->json('data.id');

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia");

    $response->assertOk()
        ->assertJsonPath('data.status', FindingStatus::SentToIa->value);

    expect(Finding::find($id)->status)->toBe(FindingStatus::SentToIa);
});

test('kirim ulang di luar draft ditolak 422', function () {
    $admin = createTestUser(Role::AdminSpi);

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-004', 'title' => 'Temuan uji',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/findings/{$id}/send-to-ia")
        ->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Hanya temuan dalam status Draft yang dapat dikirim ke IA.');
});

test('staff tidak boleh membuat temuan (403)', function () {
    $staff = createTestUser(Role::StaffDept);

    $this->actingAs($staff, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-005', 'title' => 'Tidak boleh',
    ])->assertStatus(403);
});

test('dokumen temuan diunggah ke disk private dan bisa diunduh', function () {
    $admin = createTestUser(Role::AdminSpi);

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-006', 'title' => 'Temuan dokumen',
    ])->json('data.id');

    $upload = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/documents", [
        'document' => UploadedFile::fake()->create('audit.pdf', 120, 'application/pdf'),
        'label' => 'Audit',
    ]);

    $upload->assertStatus(201)->assertJsonPath('data.label', 'Audit');

    $document = FindingDocument::first();
    expect($document->mime)->toBe('application/pdf');
    Storage::disk(config('upload.disk'))->assertExists($document->path);

    $download = $this->actingAs($admin, 'sanctum')
        ->get('/api/v1/findings/documents/' . $document->id . '/download');
    $download->assertOk();
});

test('upload dokumen ditolak untuk tipe file tidak diizinkan', function () {
    $admin = createTestUser(Role::AdminSpi);

    $id = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-007', 'title' => 'Temuan mime',
    ])->json('data.id');

    $this->actingAs($admin, 'sanctum')->postJson("/api/v1/findings/{$id}/documents", [
        'document' => UploadedFile::fake()->createWithContent('virus.exe', 'MZ binary'),
    ])->assertStatus(422);
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