<?php

use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Audit;
use App\Models\Department;
use App\Models\Finding;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class, \Database\Seeders\EmployeeUserSeeder::class]);

    $this->admin = User::where('username', 'admin_spi')->first();
    $this->managerIa = User::where('username', 'manager_ia')->first();
    $this->managerDept = User::where('username', 'mgr_finance_ict')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->managerDeptEks = User::where('username', 'mgr_eks')->first();
    $this->picEks = User::where('username', 'pic_1_eks')->first();
    $this->kepalaSpi = User::where('username', 'kepala_spi')->first();
});

function seedFindingAsAdmin(): Finding
{
    $service = new \App\Services\FindingService();
    $finding = $service->createDraft([
        'title' => 'Kelemahan kontrol persetujuan',
        'source' => 'BPK',
        'lhp_number' => 'LHP/2026/E2E',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Persetujuan pengadaan',
    ]);

    $finding->documents()->create([
        'label' => 'LHP',
        'name' => 'e2e.pdf',
        'path' => 'findings/e2e.pdf',
        'mime' => 'application/pdf',
        'size' => 1024,
    ]);

    return $service->register($finding, Department::whereIn('code', ['FINANCE_ICT', 'EKS'])->pluck('id')->all());
}


test('alur lengkap dari input hingga penunjukan PIC', function () {
    $admin = $this->admin;
    $this->actingAs($admin);

    $finding = seedFindingAsAdmin();

    // 1. Keaktifkan temuan, lalu buat action plan multi-departemen.
    $finding = (new \App\Services\FindingService())->activate($finding);
    expect($finding->status)->toEqual(FindingStatus::ProsessTindakLanjut);

    $aps = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [$this->managerDept->department_id, $this->managerDeptEks->department_id],
        'title' => 'Tindak lanjut',
        'condition' => 'Kondisi',
        'criteria' => 'Kriteria',
        'cause' => 'Sebab',
        'impact' => 'Dampak',
        'risk' => 'TINGGI',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data');

    $apIds = collect($aps)->pluck('id')->all();

    // 2. Kirim kedua action plan.
    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => $apIds])->assertOk();

    // 3. Masing-masing manager menentukan PIC.
    foreach ($aps as $ap) {
        $manager = $ap['department_id'] === $this->managerDept->department_id ? $this->managerDept : $this->managerDeptEks;
        $pic = $ap['department_id'] === $this->managerDept->department_id ? $this->pic : $this->picEks;

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [$pic->id]])
            ->assertOk();
    }

    // 4. Jejak audit mencatat tiap langkah penting.
    $actions = Audit::query()->pluck('action')->unique()->all();
    expect($actions)->toContain('finding.created')
        ->toContain('finding.registered')
        ->toContain('finding.activated')
        ->toContain('action_plan.created')
        ->toContain('action_plan.sent')
        ->toContain('action_plan.pics_assigned');
});

test('token hasil login dipakai untuk akses API', function () {
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $this->admin->email,
        'password' => 'password',
    ])->assertOk()->json('data.token');

    expect($token)->toBeString();

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
