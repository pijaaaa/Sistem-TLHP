<?php

use App\Enums\FollowUpStatus;
use App\Enums\InboxTaskStatus;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\InboxTask;
use App\Models\User;
use App\Services\FindingService;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed();
    Carbon::setTestNow('2026-10-01 08:00:00');
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
    $this->kepala = User::where('username', 'kepala_spi')->first();
});

function r9ActiveApWithPic(): array
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R9',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R9/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R9',
    ]);
    $finding->documents()->create(['label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'findings/lhp.pdf', 'mime' => 'application/pdf', 'size' => 1024]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $service->activate($finding);

    $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'Perbaikan R9',
        'risk' => 'TINGGI',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];

    test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [test()->pic->id]])->assertOk();

    return [ActionPlan::withoutGlobalScopes()->find($ap['id']), test()->manager, test()->pic, $finding];
}

function r9SubmittedFollowUp(ActionPlan $ap): FollowUp
{
    $fu = test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", [
        'rows' => [['description' => 'TL R9', 'target_date' => '2026-10-15', 'weight' => 100, 'pic_ids' => [test()->pic->id]]],
    ])->assertCreated()->json('data')[0];
    test()->actingAs(test()->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();

    return FollowUp::withoutGlobalScopes()->find($fu['id']);
}

test('pengiriman action plan membuat inbox task dan notifikasi untuk manager', function () {
    [$ap] = r9ActiveApWithPic();

    $tasks = InboxTask::withoutGlobalScopes()
        ->where('recipient_id', $this->manager->id)
        ->where('task_type', 'ACTION_PLAN_SENT')
        ->where('subject_id', $ap->id)
        ->get();

    expect($tasks)->toHaveCount(1)
        ->and($tasks[0]->status)->toEqual(InboxTaskStatus::Done);

    $this->actingAs($this->manager, 'sanctum')->getJson('/api/v1/notifications')->assertOk()
        ->assertJsonCount(1, 'data');
});

test('first_opened tercatat sekali dan keputusan manager menandai acted', function () {
    [$ap] = r9ActiveApWithPic();
    $fu = r9SubmittedFollowUp($ap);

    $this->actingAs($this->manager, 'sanctum')->postJson('/api/v1/inbox/open', [
        'subject_type' => 'follow_up', 'subject_id' => $fu->id,
    ])->assertOk()->assertJsonPath('data.opened', 1);

    $this->actingAs($this->manager, 'sanctum')->postJson('/api/v1/inbox/open', [
        'subject_type' => 'follow_up', 'subject_id' => $fu->id,
    ])->assertOk()->assertJsonPath('data.opened', 0);

    $task = InboxTask::withoutGlobalScopes()
        ->where('recipient_id', $this->manager->id)
        ->where('subject_id', $fu->id)
        ->where('task_type', 'FOLLOW_UP_SUBMITTED')
        ->first();
    expect($task->first_opened_at)->not->toBeNull();

    // Manager menyetujui → tugasnya selesai (acted_at).
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")->assertOk();

    $task->refresh();
    expect($task->status)->toEqual(InboxTaskStatus::Done)
        ->and($task->acted_at)->not->toBeNull();
});

test('completion request ke manager dan keputusan penyelesaian ke PIC', function () {
    [$ap] = r9ActiveApWithPic();
    $fu = r9SubmittedFollowUp($ap);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")->assertOk();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/progress", ['progress_value' => 100])->assertCreated();

    $managerTask = InboxTask::withoutGlobalScopes()
        ->where('recipient_id', $this->manager->id)
        ->where('task_type', 'COMPLETION_REQUESTED')
        ->where('subject_id', $fu->id)
        ->where('status', InboxTaskStatus::Open->value)
        ->first();
    expect($managerTask)->not->toBeNull();

    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve-completion")->assertOk();

    $picTask = InboxTask::withoutGlobalScopes()
        ->where('recipient_id', $this->pic->id)
        ->where('task_type', 'COMPLETION_DECIDED')
        ->where('subject_id', $fu->id)
        ->where('status', InboxTaskStatus::Open->value)
        ->first();
    expect($picTask)->not->toBeNull();
});

test('pengingat H-14 ke PIC dan idempotent dalam sehari', function () {
    [$ap] = r9ActiveApWithPic();
    $fu = r9SubmittedFollowUp($ap);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")->assertOk();

    FollowUp::withoutGlobalScopes()->whereKey($fu->id)->update(['target_date' => '2026-10-15']);

    $before = DatabaseNotification::query()->count();
    Artisan::call('reminders:daily');
    Artisan::call('reminders:daily');

    // Jalankan dua kali di hari yang sama hanya menambah satu pengingat.
    expect(DatabaseNotification::query()->count())->toBe($before + 1);
});

test('eskalasi keterlambatan ke manager dan kepala spi', function () {
    [$ap] = r9ActiveApWithPic();
    $fu = r9SubmittedFollowUp($ap);
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu->id}/approve")->assertOk();

    FollowUp::withoutGlobalScopes()->whereKey($fu->id)->update(['target_date' => '2026-09-23']);

    Artisan::call('reminders:daily');

    // Baseline 2 (ActionPlanSent + FollowUpSubmitted) → +1 eskalasi = 3.
    $managerNotifs = DatabaseNotification::query()->where('notifiable_id', $this->manager->id)->count();
    expect($managerNotifs)->toBe(3);

    // >7 hari terlambat (9 hari) → juga kepala SPI.
    $kepalaNotifs = DatabaseNotification::query()->where('notifiable_id', $this->kepala->id)->count();
    expect($kepalaNotifs)->toBe(1);
});

test('pengingat tugas belum dibuka dan pengulangan', function () {
    // Tugas dibuka lebih dari 3 hari → pengingat.
    $finding = (new FindingService())->createDraft(['title' => 'Subjek pengingat']);
    InboxTask::create([
        'recipient_id' => $this->pic->id,
        'task_type' => 'FOLLOW_UP_DECIDED',
        'subject_type' => 'App\Models\Finding',
        'subject_id' => $finding->id,
        'title' => 'Belum dibuka',
        'received_at' => now()->subDays(6),
        'status' => 'OPEN',
    ]);

    Artisan::call('reminders:daily');

    $afterFirst = DatabaseNotification::query()->where('notifiable_id', $this->pic->id)->count();
    expect($afterFirst)->toBe(1);

    // +3 hari lagi → pengulangan.
    Carbon::setTestNow('2026-10-04 08:00:00');
    Artisan::call('reminders:daily');

    $afterRepeat = DatabaseNotification::query()->where('notifiable_id', $this->pic->id)->count();
    expect($afterRepeat)->toBe(2);
});