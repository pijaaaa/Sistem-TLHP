<?php

use App\Enums\AuditorConclusion;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\Audit;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingDepartment;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Ujung ke ujung: seluruh alur temuan hingga CLOSED, memakai role nyata.
 * Autentikasi token nyata diuji terpisah di test autentikasi.
 */
beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    Storage::fake(config('upload.disk'));

    $this->admin = createTestUser(Role::AdminSpi);
    $this->managerIa = createTestUser(Role::ManagerIa, 'IA');
    $this->managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $this->pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');
    $this->managerEks = createTestUser(Role::ManagerDept, 'EKS');
    $this->picEks = createTestUser(Role::StaffDept, 'EKS');
    $this->managerSpi = createTestUser(Role::ManagerSpi);
});

function fdFor(int $findingId, string $deptCode): FindingDepartment
{
    return FindingDepartment::where('finding_id', $findingId)
        ->whereHas('department', fn ($q) => $q->where('code', $deptCode))
        ->firstOrFail();
}

test('alur lengkap temuan eksternal dari input hingga closing', function () {
    // 1. Admin SPI membuat temuan dan mengirim ke IA.
    $findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'E2E-2026-001',
        'title' => 'Kelemahan kontrol persetujuan',
        'severity' => 'high',
        'recommendation' => 'Perkuat segregasi tugas',
    ])->assertCreated()->json('data.id');

    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/v1/findings/{$findingId}/send-to-ia")->assertOk();

    expect(Finding::find($findingId)->status)->toBe(FindingStatus::SentToIa);

    // 2. Manager IA mendistribusikan ke dua departemen.
    $deptIds = Department::whereIn('code', ['FINANCE_ICT', 'EKS'])->pluck('id')->all();

    $this->actingAs($this->managerIa, 'sanctum')
        ->postJson("/api/v1/findings/{$findingId}/distribute", ['department_ids' => $deptIds])
        ->assertOk();

    expect(FindingDepartment::where('finding_id', $findingId)->count())->toBe(2);

    // 3. Masing-masing Manager Dept menugaskan PIC.
    $this->actingAs($this->managerDept, 'sanctum')
        ->postJson('/api/v1/finding-departments/' . fdFor($findingId, 'FINANCE_ICT')->id . '/assign-pics', [
            'pic_ids' => [$this->pic->id],
        ])->assertOk();

    $this->actingAs($this->managerEks, 'sanctum')
        ->postJson('/api/v1/finding-departments/' . fdFor($findingId, 'EKS')->id . '/assign-pics', [
            'pic_ids' => [$this->picEks->id],
        ])->assertOk();

    // 4. PIC membuat rencana aksi, mengajukan; Manager Dept menyetujui.
    $departments = [
        ['code' => 'FINANCE_ICT', 'pic' => $this->pic, 'manager' => $this->managerDept],
        ['code' => 'EKS', 'pic' => $this->picEks, 'manager' => $this->managerEks],
    ];

    foreach ($departments as $d) {
        $fdId = fdFor($findingId, $d['code'])->id;

        $apId = $this->actingAs($d['pic'], 'sanctum')
            ->postJson("/api/v1/finding-departments/{$fdId}/action-plans", [
                'title' => "Tindak lanjut {$d['code']}",
                'weight' => 100,
            ])->assertCreated()->json('data.id');

        $this->actingAs($d['pic'], 'sanctum')
            ->postJson("/api/v1/action-plans/{$apId}/submit")->assertOk();

        $this->actingAs($d['manager'], 'sanctum')
            ->postJson("/api/v1/action-plans/{$apId}/approve")->assertOk();
    }

    // 5. Evidence diunggah PIC dan disetujui Manager Dept.
    foreach ($departments as $d) {
        $apId = ActionPlan::where('finding_department_id', fdFor($findingId, $d['code'])->id)
            ->firstOrFail()->id;

        $this->actingAs($d['pic'], 'sanctum')
            ->post("/api/v1/action-plans/{$apId}/evidence", [
                'files' => [['file' => UploadedFile::fake()->create('bukti.pdf', 200, 'application/pdf')]],
            ])->assertCreated();

        $this->actingAs($d['manager'], 'sanctum')
            ->postJson("/api/v1/action-plans/{$apId}/evidence/approve")->assertOk();
    }

    // 6. Kedua departemen selesai 100% lalu teruskan ke IA.
    foreach ($departments as $d) {
        $fd = fdFor($findingId, $d['code']);
        expect($fd->fresh()->status->value)->toBe('selesai_100');

        $this->actingAs($d['manager'], 'sanctum')
            ->postJson("/api/v1/finding-departments/{$fd->id}/forward-to-ia")->assertOk();
    }

    expect(Finding::find($findingId)->status)->toBe(FindingStatus::PendingIaAssessment);

    // 7. Manager IA assess SSR, Manager SPI menutup temuan.
    $this->actingAs($this->managerIa, 'sanctum')
        ->postJson("/api/v1/findings/{$findingId}/assess", [
            'assessment_status' => 'ssr',
            'note' => 'Sudah memadai',
        ])->assertOk();

    expect(Finding::find($findingId)->status)->toBe(FindingStatus::PendingVerificationSpi);

    $this->actingAs($this->managerSpi, 'sanctum')
        ->postJson("/api/v1/findings/{$findingId}/verifications", [
            'auditor_conclusion' => AuditorConclusion::Closed->value,
            'auditor_result' => 'Sesuai rekomendasi',
            'notes' => 'Tidak ada temuan lanjutan',
        ])->assertCreated();

    expect(Finding::find($findingId)->status)->toBe(FindingStatus::Closed);

    // 8. Jejak audit lengkap untuk setiap langkah penting.
    $actions = Audit::where('entity_type', 'finding')
        ->where('entity_id', $findingId)
        ->pluck('action')
        ->unique();

    expect($actions->all())->toContain('finding.created');
    expect($actions->all())->toContain('finding.sent_to_ia');
    expect($actions->all())->toContain('finding.assessed');
    expect($actions->all())->toContain('finding.verified');
});

test('token hasil login dipakai untuk akses API', function () {
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $this->admin->email,
        'password' => 'password',
    ])->assertOk()->json('data.token');

    expect($token)->toBeString();

    // Header Authorization dibaca langsung oleh Sanctum.
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.user.email', $this->admin->email)
        ->assertJsonPath('data.role', 'admin_spi');
});

test('logout mencabut token sehingga tidak bisa dipakai lagi', function () {
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $this->admin->email,
        'password' => 'password',
    ])->assertOk()->json('data.token');

    expect($this->admin->tokens()->count())->toBe(1);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/auth/logout')->assertOk();

    expect($this->admin->tokens()->count())->toBe(0);
});

test('login dibatasi rate limit untuk menahan brute force', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $this->admin->email,
            'password' => 'salah',
        ])->assertStatus(401);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $this->admin->email,
        'password' => 'password',
    ])->assertStatus(429);
});