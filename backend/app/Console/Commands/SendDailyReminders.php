<?php

namespace App\Console\Commands;

use App\Enums\FollowUpStatus;
use App\Enums\InboxTaskStatus;
use App\Enums\ReminderType;
use App\Enums\Role;
use App\Models\FollowUp;
use App\Models\InboxTask;
use App\Models\ReminderLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendDailyReminders extends Command
{
    protected $signature = 'reminders:daily';

    protected $description = 'Kirim pengingat tenggat, eskalasi keterlambatan, dan pengingat tugas belum dibuka (idempotent).';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $reminders = config('reminders');

        FollowUp::withoutGlobalScopes()
            ->whereIn('status', [FollowUpStatus::Disetujui->value, FollowUpStatus::MenungguPersetujuanSelesai->value])
            ->whereNotNull('target_date')
            ->with(['actionPlan.department', 'actionPlan.finding'])
            ->chunkById(200, function ($followUps) use ($today, $reminders) {
                foreach ($followUps as $fu) {
                    $target = $fu->target_date->copy()->startOfDay();

                    if ($target >= $today) {
                        $dueIn = (int) $today->diffInDays($target);
                        if (in_array($dueIn, $reminders['before_days'], true)) {
                            foreach ($this->picsOf($fu) as $pic) {
                                $this->notify($pic, ReminderType::HMinus, $fu, "Tenggat H-{$dueIn}: tindak lanjut #{$fu->id} ({$fu->target_date->toDateString()})");
                            }
                        }
                        continue;
                    }

                    $overdue = (int) $target->diffInDays($today);
                    foreach ($this->managersOf($fu) as $manager) {
                        $this->notify($manager, ReminderType::LateManager, $fu, "Tindak lanjut #{$fu->id} terlambat {$overdue} hari");
                    }

                    if ($overdue > (int) $reminders['escalate_kepala_spi_after']) {
                        foreach (User::where('role', Role::Kepala_spi->value)->where('is_active', true)->get() as $kepala) {
                            $this->notify($kepala, ReminderType::LateKepala, $fu, "Tindak lanjut #{$fu->id} terlambat > {$reminders['escalate_kepala_spi_after']} hari");
                        }
                    }
                }
            });

        $this->remindUnopened($today, $reminders);

        return self::SUCCESS;
    }

    private function remindUnopened(\Illuminate\Support\Carbon $today, array $reminders): void
    {
        $threshold = (int) $reminders['unopened_after_days'];
        $repeat = (int) $reminders['unopened_repeat_days'];

        InboxTask::where('status', InboxTaskStatus::Open->value)
            ->whereNull('first_opened_at')
            ->where('received_at', '<', $today->copy()->subDays($threshold))
            ->chunkById(500, function ($tasks) use ($today, $threshold, $repeat) {
                foreach ($tasks as $task) {
                    $received = $task->received_at->copy()->startOfDay();
                    $since = (int) $received->diffInDays($today);

                    if ($since >= $threshold && $since % $repeat === 0) {
                        $recipient = User::find($task->recipient_id);
                        if (!$recipient) {
                            continue;
                        }
                        $subject = $task->subject()->first();
                        if (!$subject) {
                            continue;
                        }
                        $this->notify($recipient, ReminderType::Unopened, $subject, "Pengingat belum dibuka: {$task->title}", $subject->getKey());
                    }
                }
            });
    }

    private function notify(User $user, ReminderType $type, \Illuminate\Database\Eloquent\Model $subject, string $title): void
    {
        $already = ReminderLog::where('user_id', $user->id)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('reminder_type', $type->value)
            ->whereDate('sent_on', now()->toDateString())
            ->exists();

        if ($already) {
            return;
        }

        ReminderLog::create([
            'user_id' => $user->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'reminder_type' => $type,
            'sent_on' => now()->toDateString(),
        ]);

        NotificationFacade::send($user, new class($title) extends Notification {
            public function __construct(private readonly string $title) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return ['title' => $this->title, 'data' => []];
            }
        });
    }

    private function picsOf(FollowUp $fu): \Illuminate\Support\Collection
    {
        return User::whereIn('id', function ($q) use ($fu) {
            $q->select('user_id')->from('follow_up_assignees')->where('follow_up_id', $fu->id);
        })->get();
    }

    private function managersOf(FollowUp $fu): \Illuminate\Support\Collection
    {
        $deptId = $fu->actionPlan->department_id;

        return User::where('role', Role::ManagerDept->value)
            ->where('department_id', $deptId)
            ->where('is_active', true)
            ->get();
    }
}