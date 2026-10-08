<?php

use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Enums\ReviewDecision;
use App\Events\FollowUpDecided;
use App\Events\FollowUpReturnedToRevision;
use App\Events\IaCommentAdded;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\FollowUpComment;
use App\Models\FollowUpReview;
use App\Models\User;
use App\Services\FindingService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
    $this->managerEks = User::where('username', 'mgr_eks')->first();
    $this->ia = User::where('username', 'internal_audit')->first();
});

function r5ReadyActionPlan(): ActionPlan
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R5',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R5/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R5',
    ]);
    $finding->documents()->create([
        'label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'findings/lhp.pdf', 'mime' => 'application/pdf', 'size' => 1024,
    ]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $service->activate($finding);

    $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'Perbaikan R5',
        'risk' => 'TINGGI',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];

    test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    test()->actingAs(test()->manager, 'sanctum')
        ->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [test()->pic->id]])
        ->assertOk();

    return ActionPlan::withoutGlobalScopes()->find($ap['id']);
}

function submittedFollowUp(ActionPlan $ap, string $description, int $weight): FollowUp
{
    $fu = test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", [
        'rows' => [
            ['description' => $description, 'target_date' => '2026-10-01', 'weight' => $weight, 'pic_ids' => [test()->pic->id]],
        ],
    ])->assertCreated()->json('data')[0];

    test()->actingAs(test()->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();

    return FollowUp::withoutGlobalScopes()->find($fu['id']);
}

test('manager menyetujui dan menolak per tindak lanjut', function () {
    Event::fake([FollowUpDecided::class]);
    $ap = r5ReadyActionPlan();

    $approved = submittedFollowUp($ap, 'Disetujui', 40);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$approved->id}/approve")->assertOk()
        ->assertJsonPath('data.status', FollowUpStatus::Disetujui->value);

    expect($approved->refresh()->approved_at)->not->toBeNull();
    expect(FollowUpReview::where('follow_up_id', $approved->id)->where('decision', ReviewDecision::Setujui->value)->exists())->toBeTrue();
    Event::assertDispatched(FollowUpDecided::class);

    $revised = submittedFollowUp($ap, 'Direvisi', 30);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$revised->id}/revision", [
        'note' => 'Perjelas langkah',
    ])->assertOk()->assertJsonPath('data.status', FollowUpStatus::Revisi->value);

    $rejected = submittedFollowUp($ap, 'Ditolak', 30);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$rejected->id}/reject", [
        'note' => 'Tidak relevan',
    ])->assertOk()->assertJsonPath('data.status', FollowUpStatus::Ditolak->value);
});

test('catatan wajib untuk revisi dan tolak', function () {
    $ap = r5ReadyActionPlan();
    $fu = submittedFollowUp($ap, 'Tanpa catatan', 50);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/revision", [])->assertStatus(422);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/reject", [])->assertStatus(422);
});

test('tindak lanjut ditolak bersifat final (tidak dapat diajukan ulang)', function () {
    $ap = r5ReadyActionPlan();
    $fu = submittedFollowUp($ap, 'Ditolak final', 50);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/reject", [
        'note' => 'Tutup',
    ])->assertOk();

    $this->actingAs($this->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu->id]])
        ->assertStatus(422);

    expect($fu->refresh()->status)->toEqual(FollowUpStatus::Ditolak);
});

test('override bobot oleh manager dan batas 100', function () {
    $ap = r5ReadyActionPlan();
    $a = submittedFollowUp($ap, 'A 40', 40);
    $b = submittedFollowUp($ap, 'B 40', 40);

    $this->actingAs($this->manager, 'sanctum')->patchJson("/api/v1/follow-ups/{$b->id}/weight", ['weight' => 70])
        ->assertStatus(422);

    $this->actingAs($this->manager, 'sanctum')->patchJson("/api/v1/follow-ups/{$b->id}/weight", ['weight' => 60])
        ->assertOk()->assertJsonPath('data.weight', 60);

    $review = FollowUpReview::where('follow_up_id', $b->id)->where('decision', ReviewDecision::OverrideBobot->value)->first();
    expect($review)->not->toBeNull();
    expect((int) $review->weight_before)->toBe(40)
        ->and((int) $review->weight_after)->toBe(60);
});

test('manager departemen lain tidak bisa memutuskan tindak lanjut', function () {
    $ap = r5ReadyActionPlan();
    $fu = submittedFollowUp($ap, 'Departemen asing', 100);

    // AP departemen FINANCE_ICT tidak terlihat oleh manager EKS (visibilitas).
    $this->actingAs($this->managerEks, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")
        ->assertNotFound();
});

test('internal audit hanya berkomentar pada tindak lanjut yang sudah disetujui', function () {
    $ap = r5ReadyActionPlan();
    $draft = submittedFollowUp($ap, 'Belum disetujui', 50);

    // Belum disetujui → tidak terlihat pemantau → 404.
    $this->actingAs($this->ia, 'sanctum')->postJson("/api/v1/follow-ups/{$draft->id}/comments", [
        'kind' => 'IA_COMMENT', 'body' => 'Cek lagi.',
    ])->assertNotFound();

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$draft->id}/approve")->assertOk();

    $this->actingAs($this->ia, 'sanctum')->postJson("/api/v1/follow-ups/{$draft->id}/comments", [
        'kind' => 'IA_COMMENT', 'body' => 'Sudah memadai.',
    ])->assertCreated()->assertJsonPath('data.kind', 'IA_COMMENT');

    // Komentar terlihat PIC.
    $this->actingAs($this->pic, 'sanctum')->getJson("/api/v1/follow-ups/{$draft->id}/comments")->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Sudah memadai.');
});

test('pengembalian ke revisi atas masukan IA mempertahankan progres', function () {
    Event::fake([FollowUpReturnedToRevision::class]);
    $ap = r5ReadyActionPlan();
    $fu = submittedFollowUp($ap, 'Sudah disetujui', 100);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")->assertOk();

    FollowUp::withoutGlobalScopes()->whereKey($fu->id)->update(['progress' => 50]);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/return-to-revision", [
        'note' => 'IA minta penambahan bukti',
    ])->assertOk()->assertJsonPath('data.status', FollowUpStatus::Revisi->value);

    Event::assertDispatched(FollowUpReturnedToRevision::class);

    expect($fu->refresh()->progress)->toBe(50);
});

test('event komentar IA didispatch', function () {
    Event::fake([IaCommentAdded::class]);
    $ap = r5ReadyActionPlan();
    $fu = submittedFollowUp($ap, 'Dikomentari', 100);

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")->assertOk();

    $this->actingAs($this->ia, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/comments", [
        'kind' => 'IA_COMMENT', 'body' => 'Catatan IA',
    ])->assertCreated();

    Event::assertDispatched(IaCommentAdded::class);
});