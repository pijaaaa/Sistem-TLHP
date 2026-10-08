<?php

use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\FindingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    Storage::fake(config('upload.disk'));
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->kepala = User::where('username', 'kepala_spi')->first();
    $this->ia = User::where('username', 'internal_audit')->first();
    $this->managerF = User::where('username', 'mgr_finance_ict')->first();
    $this->picF = User::where('username', 'pic_1_finance_ict')->first();
    $this->managerE = User::where('username', 'mgr_eks')->first();
    $this->picE = User::where('username', 'pic_1_eks')->first();
});

function f11Finding(): \App\Models\Finding
{
    $svc = new FindingService();
    $f = $svc->createDraft([
        'title' => 'Temuan Full Flow', 'source' => 'BPK', 'source_name' => null,
        'lhp_number' => 'LHP/FF/01', 'lhp_date' => '2026-06-01', 'finding_date' => '2026-06-15',
        'response_period_start' => '2026-07-01', 'response_period_end' => '2026-12-31', 'scope' => 'Audit FF',
    ]);
    $f->documents()->create(['label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1]);
    $svc->register($f, [Department::where('code', 'FINANCE_ICT')->first()->id, Department::where('code', 'EKS')->first()->id]);
    return $svc->activate($f);
}

function f11Ap(int $findingId, string $deptCode): array
{
    $isEks = $deptCode === 'EKS';
    $manager = $isEks ? test()->managerE : test()->managerF;
    $pic = $isEks ? test()->picE : test()->picF;
    $dept = Department::where('code', $deptCode)->first();
    $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $findingId,
        'department_ids' => [$dept->id],
        'title' => "AP {$deptCode}", 'risk' => 'TINGGI', 'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];
    test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    test()->actingAs($manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [$pic->id]])->assertOk();

    return $ap;
}

function f11ActiveIds(int $apId): \Illuminate\Support\Collection
{
    $ap = ActionPlan::withoutGlobalScopes()->find($apId);

    return FollowUp::withoutGlobalScopes()
        ->where('action_plan_id', $apId)
        ->where('revision_no', $ap->current_revision)
        ->where('status', '!=', 'DITOLAK')
        ->pluck('id')
        ->values();
}

function f11FinishAp(int $apId, string $deptCode, array $decisions = ['SESUAI']): void
{
    $pic = $deptCode === 'EKS' ? test()->picE : test()->picF;
    $manager = $deptCode === 'EKS' ? test()->managerE : test()->managerF;

    // Susun TL baru, ajukan, setujui, lengkapi 100, ajukan ke SPI.
    $fu = test()->actingAs($pic, 'sanctum')->postJson("/api/v1/action-plans/{$apId}/follow-ups", [
        'rows' => [['description' => "TL {$deptCode}", 'target_date' => '2026-10-20', 'weight' => 100, 'pic_ids' => [$pic->id]]],
    ])->assertCreated()->json('data')[0];
    test()->actingAs($pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();
    test()->actingAs($manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve")->assertOk();
    test()->actingAs($pic, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/progress", ['progress_value' => 100])->assertCreated();
    test()->actingAs($manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve-completion")->assertOk();
    test()->actingAs($manager, 'sanctum')->postJson("/api/v1/action-plans/{$apId}/submit-to-spi")->assertOk();

    // Review SPI sesuai keputusan.
    $ids = f11ActiveIds($apId);
    test()->actingAs(test()->admin, 'sanctum')->postJson("/api/v1/action-plans/{$apId}/spi-review", [
        'items' => $ids->values()->map(fn ($id, $i) => [
            'follow_up_id' => $id,
            'result' => $decisions[$i] ?? 'SESUAI',
            'note' => ($decisions[$i] ?? 'SESUAI') === 'REVISI' ? 'Perbaiki' : null,
        ])->all(),
    ])->assertOk();
    test()->actingAs(test()->admin, 'sanctum')->postJson("/api/v1/action-plans/{$apId}/spi-complete")->assertOk();
}

test('alur penuh: registrasi hingga SSR menutup temuan dan PIC kehilangan akses', function () {
    $finding = f11Finding();

    // Dua departemen setelah diaktifkan (create AP dulu sebelum status bergeser).
    $apF = f11Ap($finding->id, 'FINANCE_ICT');
    $apE = f11Ap($finding->id, 'EKS');

    // FINANCE: review SPI meminta revisi (revisi 1).
    f11FinishAp($apF['id'], 'FINANCE_ICT', ['REVISI']);
    $apFModel = ActionPlan::withoutGlobalScopes()->find($apF['id']);
    expect($apFModel->status->value)->toBe('REVISI_SPI')
        ->and($apFModel->current_revision)->toBe(1);

    // EKS tuntas Sesuai.
    f11FinishAp($apE['id'], 'EKS', ['SESUAI']);
    expect(ActionPlan::withoutGlobalScopes()->find($apE['id'])->status->value)->toBe('SESUAI');

    // Manager meneruskan revisi 1 ke PIC; PIC menyusun TL baru.
    test()->actingAs($this->managerF, 'sanctum')->postJson("/api/v1/action-plans/{$apF['id']}/forward-to-pic")->assertOk();

    $fuF = test()->actingAs($this->picF, 'sanctum')->postJson("/api/v1/action-plans/{$apF['id']}/follow-ups", [
        'rows' => [['description' => 'TL revisi 1', 'target_date' => '2026-11-01', 'weight' => 100, 'pic_ids' => [$this->picF->id]]],
    ])->assertCreated()->json('data')[0];
    test()->actingAs($this->picF, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fuF['id']]])->assertOk();
    test()->actingAs($this->managerF, 'sanctum')->postJson("/api/v1/follow-ups/{$fuF['id']}/approve")->assertOk();
    test()->actingAs($this->picF, 'sanctum')->postJson("/api/v1/follow-ups/{$fuF['id']}/progress", ['progress_value' => 100])->assertCreated();
    test()->actingAs($this->managerF, 'sanctum')->postJson("/api/v1/follow-ups/{$fuF['id']}/approve-completion")->assertOk();
    test()->actingAs($this->managerF, 'sanctum')->postJson("/api/v1/action-plans/{$apF['id']}/submit-to-spi")->assertOk();

    // Review revisi 1: semua SESUAI.
    $idsF = f11ActiveIds($apF['id']);
    test()->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$apF['id']}/spi-review", [
        'items' => $idsF->map(fn ($id) => ['follow_up_id' => $id, 'result' => 'SESUAI'])->all(),
    ])->assertOk();
    test()->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$apF['id']}/spi-complete")->assertOk();

    // Semua AP Sesuai → temuan menunggu status eksternal.
    expect($finding->refresh()->status)->toEqual(FindingStatus::MenungguStatusEksternal);

    // Kepala SPI: BSR untuk 1 AP (revisi 2) → temuan kembali proses.
    $apEId = $apE['id'];
    test()->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", [
        'status' => 'BSR',
        'note' => 'Perlu perbaikan lanjutan',
        'action_plan_ids' => [$apEId],
        'document' => [['file' => UploadedFile::fake()->create('hasil.pdf', 100, 'application/pdf'), 'label' => 'Hasil Auditor']],
    ])->assertCreated();

    expect(ActionPlan::withoutGlobalScopes()->find($apEId)->status->value)->toBe('REVISI_SPI')
        ->and($finding->refresh()->status)->toEqual(FindingStatus::ProsessTindakLanjut);

    // EKS: selesaikan revisi 2 sampai Sesuai.
    test()->actingAs($this->managerE, 'sanctum')->postJson("/api/v1/action-plans/{$apEId}/forward-to-pic")->assertOk();
    $fuE = test()->actingAs($this->picE, 'sanctum')->postJson("/api/v1/action-plans/{$apEId}/follow-ups", [
        'rows' => [['description' => 'TL revisi 2', 'target_date' => '2026-11-10', 'weight' => 100, 'pic_ids' => [$this->picE->id]]],
    ])->assertCreated()->json('data')[0];
    test()->actingAs($this->picE, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fuE['id']]])->assertOk();
    test()->actingAs($this->managerE, 'sanctum')->postJson("/api/v1/follow-ups/{$fuE['id']}/approve")->assertOk();
    test()->actingAs($this->picE, 'sanctum')->postJson("/api/v1/follow-ups/{$fuE['id']}/progress", ['progress_value' => 100])->assertCreated();
    test()->actingAs($this->managerE, 'sanctum')->postJson("/api/v1/follow-ups/{$fuE['id']}/approve-completion")->assertOk();
    test()->actingAs($this->managerE, 'sanctum')->postJson("/api/v1/action-plans/{$apEId}/submit-to-spi")->assertOk();

    $idsE = f11ActiveIds($apEId);
    test()->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$apEId}/spi-review", [
        'items' => $idsE->map(fn ($id) => ['follow_up_id' => $id, 'result' => 'SESUAI'])->all(),
    ])->assertOk();
    test()->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$apEId}/spi-complete")->assertOk();

    expect($finding->refresh()->status)->toEqual(FindingStatus::MenungguStatusEksternal);

    // SSR → temuan & semua AP Closed.
    test()->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", [
        'status' => 'SSR',
        'note' => 'Puas',
        'document' => [['file' => UploadedFile::fake()->create('ssr.pdf', 100, 'application/pdf'), 'label' => 'SSR']],
    ])->assertCreated();

    expect($finding->refresh()->status)->toEqual(FindingStatus::Closed);
    $aps = ActionPlan::withoutGlobalScopes()->where('finding_id', $finding->id)->get();
    expect($aps->pluck('status')->unique()->all())->toBe([\App\Enums\ActionPlanStatus::Closed]);

    // PIC kehilangan akses.
    test()->actingAs($this->picF, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()->assertJsonCount(0, 'data.data');
    test()->actingAs($this->picF, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()->assertJsonCount(0, 'data.data');

    // Manager tetap melihat.
    test()->actingAs($this->managerF, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()->assertJsonCount(1, 'data.data');
});