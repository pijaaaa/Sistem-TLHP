<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Events\ActionPlanSubmittedToSpi;
use App\Events\CompletionDecided;
use App\Events\CompletionRequested;
use App\Events\ProgressReported;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\FollowUpProgressReport;
use App\Models\User;
use App\Services\FindingService;
use App\Services\FindingStatusService;
use App\Services\ProgressService;
use App\Support\CacheService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
    $this->ia = User::where('username', 'internal_audit')->first();
});

function r6ReadyAp(array $weights = [100], bool $approve = true): ActionPlan
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R6',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R6/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R6',
    ]);
    $finding->documents()->create([
        'label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'findings/lhp.pdf', 'mime' => 'application/pdf', 'size' => 1024,
    ]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $service->activate($finding);

    $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'Perbaikan R6',
        'risk' => 'TINGGI',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];

    test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    test()->actingAs(test()->manager, 'sanctum')
        ->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [test()->pic->id]])
        ->assertOk();

    foreach ($weights as $i => $weight) {
        $fu = test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/follow-ups", [
            'rows' => [
                ['description' => "TL {$i}", 'target_date' => '2026-10-01', 'weight' => $weight, 'pic_ids' => [test()->pic->id]],
            ],
        ])->assertCreated()->json('data')[0];
        test()->actingAs(test()->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();
        if ($approve) {
            test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve")->assertOk();
        }
    }

    return ActionPlan::withoutGlobalScopes()->find($ap['id']);
}

function reportProgress(FollowUp $fu, int $value, ?string $note = null): void
{
    test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/progress", [
        'progress_value' => $value,
        'note' => $note,
    ])->assertCreated();
}

function completeFollowUp(FollowUp $fu): void
{
    reportProgress($fu, 100, 'Selesai');
    test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve-completion")->assertOk()
        ->assertJsonPath('data.status', FollowUpStatus::Selesai->value);
}

test('progres tidak boleh turun', function () {
    $ap = r6ReadyAp([100]);
    $fu = $ap->follow_ups()->withoutGlobalScopes()->first();

    reportProgress($fu, 60, 'Setengah jalan');

    test()->actingAs($this->pic, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/progress", [
        'progress_value' => 50,
    ])->assertStatus(422);
});

test('lapor 100 memicu status menunggu persetujuan selesai', function () {
    Event::fake([CompletionRequested::class, ProgressReported::class]);
    $ap = r6ReadyAp([100]);
    $fu = $ap->follow_ups()->withoutGlobalScopes()->first();

    reportProgress($fu, 50, 'Pertama');
    expect($fu->refresh()->status)->toEqual(FollowUpStatus::Disetujui)
        ->and($fu->progress)->toBe(50);
    Event::assertDispatched(ProgressReported::class);

    reportProgress($fu, 100, 'Rampung');
    expect($fu->refresh()->status)->toEqual(FollowUpStatus::MenungguPersetujuanSelesai);
    Event::assertDispatched(CompletionRequested::class);
});

test('penyelesaian oleh manager dan revisi penyelesaian mempertahankan progres', function () {
    Event::fake([CompletionDecided::class]);
    $ap = r6ReadyAp([100]);
    $fu = $ap->follow_ups()->withoutGlobalScopes()->first();
    reportProgress($fu, 100);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve-completion")->assertOk()
        ->assertJsonPath('data.status', FollowUpStatus::Selesai->value);
    Event::assertDispatched(CompletionDecided::class);

    expect($fu->refresh()->completed_at)->not->toBeNull();

    // Simulasi TL lain menunggu → minta revisi penyelesaian.
    $ap2 = r6ReadyAp([100]);
    $fu2 = $ap2->follow_ups()->withoutGlobalScopes()->first();
    reportProgress($fu2, 100);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu2->id}/completion-revision", [
        'note' => 'Dokumen belum lengkap',
    ])->assertOk()->assertJsonPath('data.status', FollowUpStatus::Disetujui->value);

    expect($fu2->refresh()->progress)->toBe(100);
});

test('rumus bobot progres action plan dan pengaruh tindak lanjut ditolak', function () {
    $ap = r6ReadyAp([40, 40, 20], false);
    $tls = $ap->follow_ups()->withoutGlobalScopes()->orderBy('id')->get();

    // TL bobot 20 ditolak saat DIAJUKAN → tidak dihitung.
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$tls[2]->id}/reject", [
        'note' => 'Tidak dipakai',
    ])->assertOk()->assertJsonPath('data.status', FollowUpStatus::Ditolak->value);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$tls[0]->id}/approve")->assertOk();
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$tls[1]->id}/approve")->assertOk();

    // (40*50 + 40*25) / 80 = 37.5
    reportProgress($tls[0], 50);
    reportProgress($tls[1], 25);

    expect((float) $ap->refresh()->progress)->toBe(37.5);
});

test('progres temuan merupakan rata-rata progres action plan', function () {
    $ap1 = r6ReadyAp([100]);
    $fu1 = $ap1->follow_ups()->withoutGlobalScopes()->first();
    reportProgress($fu1, 50);

    // AP kedua pada temuan yang sama.
    $finding = $ap1->finding;
    $ap2 = test()->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'AP kedua',
        'risk' => 'SEDANG',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];
    test()->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap2['id']]])->assertOk();
    test()->actingAs($this->manager, 'sanctum')
        ->postJson("/api/v1/action-plans/{$ap2['id']}/assign-pics", ['user_ids' => [$this->pic->id]])
        ->assertOk();

    $fu2 = test()->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap2['id']}/follow-ups", [
        'rows' => [
            ['description' => 'TL2', 'target_date' => '2026-10-01', 'weight' => 100, 'pic_ids' => [$this->pic->id]],
        ],
    ])->assertCreated()->json('data')[0];
    test()->actingAs($this->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu2['id']]])->assertOk();
    test()->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu2['id']}/approve")->assertOk();
    reportProgress(FollowUp::withoutGlobalScopes()->find($fu2['id']), 20);

    $progress = (new ProgressService())->findingProgress($finding->refresh());
    expect($progress)->toBe(35.0);
});

test('ajukan ke admin SPI menolak bobot tidak 100 atau tindak lanjut belum selesai', function () {
    // Bobot 60 ≠ 100.
    $apPartial = r6ReadyAp([60]);
    $fuPartial = $apPartial->follow_ups()->withoutGlobalScopes()->first();
    completeFollowUp($fuPartial);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$apPartial->id}/submit-to-spi")
        ->assertStatus(422);

    // TL belum Selesai.
    $apPending = r6ReadyAp([100]);
    $fuPending = $apPending->follow_ups()->withoutGlobalScopes()->first();
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$apPending->id}/submit-to-spi")
        ->assertStatus(422);
});

test('ajukan ke admin SPI sukses mengubah status temuan menjadi review SPI', function () {
    Event::fake([ActionPlanSubmittedToSpi::class]);
    $ap = r6ReadyAp([100]);
    $fu = $ap->follow_ups()->withoutGlobalScopes()->first();
    completeFollowUp($fu);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/submit-to-spi")->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::DiajukanKeSpi->value);

    Event::assertDispatched(ActionPlanSubmittedToSpi::class);

    expect($ap->finding->refresh()->status)->toEqual(FindingStatus::ReviewSpi);
});

test('cache terinvalidasi saat progres berubah', function () {
    $beforeFindings = CacheService::version('findings');
    $beforeDashboard = CacheService::version('dashboard');
    $beforeAp = CacheService::version('action_plans');

    $ap = r6ReadyAp([100]);
    $fu = $ap->follow_ups()->withoutGlobalScopes()->first();
    reportProgress($fu, 40);

    expect(CacheService::version('findings'))->toBeGreaterThan($beforeFindings)
        ->and(CacheService::version('dashboard'))->toBeGreaterThan($beforeDashboard)
        ->and(CacheService::version('action_plans'))->toBeGreaterThan($beforeAp);
});