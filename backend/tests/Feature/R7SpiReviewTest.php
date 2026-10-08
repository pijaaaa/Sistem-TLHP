<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Events\RevisionForwarded;
use App\Events\SpiReviewCompleted;
use App\Models\ActionPlan;
use App\Models\ActionPlanRevision;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\SpiReview;
use App\Models\User;
use App\Services\FindingService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
});

function r7ReadyApSubmitted(array $weights = [100]): ActionPlan
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R7',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R7/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R7',
    ]);
    $finding->documents()->create([
        'label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'findings/lhp.pdf', 'mime' => 'application/pdf', 'size' => 1024,
    ]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $service->activate($finding);

    $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'Perbaikan R7',
        'risk' => 'KRITIS',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];

    test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", [
        'user_ids' => [test()->pic->id],
    ])->assertOk();

    $tls = [];
    foreach ($weights as $i => $weight) {
        $fu = test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/follow-ups", [
            'rows' => [['description' => "TL {$i}", 'target_date' => '2026-10-01', 'weight' => $weight, 'pic_ids' => [test()->pic->id]]],
        ])->assertCreated()->json('data')[0];
        test()->actingAs(test()->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();
        test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve")->assertOk();
        test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/progress", ['progress_value' => 100])->assertCreated();
        test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve-completion")->assertOk();
        $tls[] = $fu['id'];
    }

    test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/submit-to-spi")->assertOk();

    $actionPlan = ActionPlan::withoutGlobalScopes()->find($ap['id']);

    return $actionPlan;
}

function assess(ActionPlan $ap, array $items): void
{
    test()->actingAs(test()->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-review", [
        'items' => $items,
    ])->assertOk();
}

function activeTlIds(ActionPlan $ap): array
{
    return FollowUp::withoutGlobalScopes()
        ->where('action_plan_id', $ap->id)
        ->where('status', '!=', FollowUpStatus::Ditolak->value)
        ->orderBy('id')
        ->pluck('id')
        ->all();
}

test('hanya admin spi yang dapat melakukan review', function () {
    $ap = r7ReadyApSubmitted([100]);
    $id = activeTlIds($ap)[0];

    // Manager dicekal izin menu spi_review update.
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-review", [
        'items' => [['follow_up_id' => $id, 'result' => 'SESUAI']],
    ])->assertStatus(403);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-complete")->assertStatus(403);
});

test('catatan wajib untuk penilaian revisi', function () {
    $ap = r7ReadyApSubmitted([100]);
    $id = activeTlIds($ap)[0];

    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-review", [
        'items' => [['follow_up_id' => $id, 'result' => 'REVISI']],
    ])->assertStatus(422);
});

test('complete ditolak bila ada tindak lanjut belum dinilai', function () {
    $ap = r7ReadyApSubmitted([40, 60]);
    $ids = activeTlIds($ap);

    assess($ap, [['follow_up_id' => $ids[0], 'result' => 'SESUAI']]);

    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-complete")
        ->assertStatus(422);
});

test('revisi menaikkan nomor revisi dan mencatat peminta', function () {
    Event::fake([SpiReviewCompleted::class]);
    $ap = r7ReadyApSubmitted([40, 60]);
    $ids = activeTlIds($ap);

    assess($ap, [
        ['follow_up_id' => $ids[0], 'result' => 'REVISI', 'note' => 'Perbaiki uraian'],
        ['follow_up_id' => $ids[1], 'result' => 'SESUAI'],
    ]);

    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-complete")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::RevisiSpi->value)
        ->assertJsonPath('data.current_revision', 1);

    Event::assertDispatched(SpiReviewCompleted::class, fn ($e) => $e->revised === true);

    $revision = ActionPlanRevision::where('action_plan_id', $ap->id)->first();
    expect($revision)->not->toBeNull();
    expect($revision->revision_no)->toBe(1)
        ->and($revision->source->value)->toBe('SPI_REVIEW')
        ->and($revision->requested_by)->toBe($this->admin->id)
        ->and($revision->reason)->toContain('Perbaiki uraian')
        ->and($revision->requested_at)->not->toBeNull();

    // Status temuan kembali Proses karena ada AP yang direvisi.
    expect($ap->finding->refresh()->status)->toEqual(FindingStatus::ProsessTindakLanjut);
});

test('forward ke pic mengaktifkan kembali action plan', function () {
    Event::fake([RevisionForwarded::class]);
    $ap = r7ReadyApSubmitted([100]);
    $id = activeTlIds($ap)[0];

    assess($ap, [['follow_up_id' => $id, 'result' => 'REVISI', 'note' => 'Perlu tambahan']]);
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/v1/action-plans/{$ap->id}/spi-complete", ['new_deadline' => '2027-01-31'])
        ->assertOk();

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/forward-to-pic")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::ProsesTindakLanjut->value);

    Event::assertDispatched(RevisionForwarded::class);

    $revision = ActionPlanRevision::where('action_plan_id', $ap->id)->first();
    expect($revision->forwarded_to_pic_at)->not->toBeNull();
    expect($ap->refresh()->deadline->toDateString())->toBe('2027-01-31');
});

test('tindak lanjut lama tetap tersimpan dan read-only', function () {
    $ap = r7ReadyApSubmitted([100]);
    $oldFu = FollowUp::withoutGlobalScopes()->where('action_plan_id', $ap->id)->first();

    assess($ap, [['follow_up_id' => $oldFu->id, 'result' => 'REVISI', 'note' => 'Revisi']]);
    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-complete")->assertOk();
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/forward-to-pic")->assertOk();

    // TL lama masih ada.
    expect(FollowUp::withoutGlobalScopes()->whereKey($oldFu->id)->exists())->toBeTrue();

    // Tidak bisa diedit (SELESAI → bukan DRAFT/REVISI).
    $this->actingAs($this->pic, 'sanctum')->putJson("/api/v1/follow-ups/{$oldFu->id}", ['description' => 'ubah'])
        ->assertStatus(422);
});

test('action plan sesua i dibekukan', function () {
    $ap = r7ReadyApSubmitted([100]);
    $id = activeTlIds($ap)[0];

    assess($ap, [['follow_up_id' => $id, 'result' => 'SESUAI']]);
    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-complete")
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::Sesuai->value);

    expect($ap->finding->refresh()->status)->toEqual(FindingStatus::MenungguStatusEksternal);

    // Manajer tidak bisa menunjuk PIC lagi dan override bobot ditolak.
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/assign-pics", [
        'user_ids' => [$this->pic->id],
    ])->assertStatus(422);

    test()->actingAs(test()->manager, 'sanctum')->patchJson("/api/v1/follow-ups/{$id}/weight", ['weight' => 50])
        ->assertStatus(422);
});

test('review bundle menampilkan revisi, tindak lanjut berkelompok, dan penilaian SPI', function () {
    $ap = r7ReadyApSubmitted([100]);
    $id = activeTlIds($ap)[0];

    assess($ap, [['follow_up_id' => $id, 'result' => 'REVISI', 'note' => 'Catatan SPI']]);

    $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/action-plans/{$ap->id}/review-bundle")->assertOk()
        ->assertJsonPath('data.action_plan.status', ActionPlanStatus::DiajukanKeSpi->value)
        ->assertJsonPath('data.follow_ups.0.0.spi_item.result', 'REVISI')
        ->assertJsonPath('data.follow_ups.0.0.spi_item.note', 'Catatan SPI');
});