<?php

namespace App\Services;

use App\Enums\InboxTaskStatus;
use App\Enums\InboxTaskType;
use App\Enums\Role;
use App\Events\ActionPlanSent;
use App\Events\ActionPlanSubmittedToSpi;
use App\Events\CompletionDecided;
use App\Events\CompletionRequested;
use App\Events\ExternalStatusRecorded;
use App\Events\FindingClosed;
use App\Events\FindingWaitingExternal;
use App\Events\FollowUpDecided;
use App\Events\FollowUpReturnedToRevision;
use App\Events\FollowUpSubmitted;
use App\Events\IaCommentAdded;
use App\Events\PicAssigned;
use App\Events\ProgressReported;
use App\Events\RevisionForwarded;
use App\Events\SpiReviewCompleted;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Models\InboxTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class TaskDispatcher
{
    /** Kirim inbox task + notifikasi ke satu penerima. */
    public static function send(User $recipient, InboxTaskType $type, Model $subject, string $title, ?int $createdBy = null, ?string $url = null): void
    {
        if (!self::hasAccess($recipient, $subject)) {
            return;
        }

        InboxTask::create([
            'recipient_id' => $recipient->id,
            'task_type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'title' => $title,
            'received_at' => now(),
            'status' => InboxTaskStatus::Open,
            'created_by' => $createdBy,
        ]);

        NotificationFacade::send($recipient, new class($title, $url) extends Notification {
            public function __construct(private readonly string $title, private readonly ?string $url) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return ['title' => $this->title, 'data' => ['url' => $this->url]];
            }
        });

        \App\Support\CacheService::flushGroup('dashboard');
        \App\Support\CacheService::flushGroup('reports');
    }

    /** Tandai tugas penerima pada subjek selesai + acted_at (satu aksi menutup tugas menunggunya). */
    public static function completeForActor(Model $subject, ?int $actorId = null): void
    {
        $actorId = $actorId ?? auth()->id();
        if (!$actorId) {
            return;
        }

        InboxTask::where('recipient_id', $actorId)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('status', InboxTaskStatus::Open->value)
            ->update([
                'status' => InboxTaskStatus::Done->value,
                'acted_at' => now(),
            ]);
    }

    /** Tandai first_opened_at sekali untuk tugas terbuka penerima pada subjek. */
    public static function markOpened(Model $subject, ?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        return InboxTask::where('recipient_id', $userId)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('status', InboxTaskStatus::Open->value)
            ->whereNull('first_opened_at')
            ->update(['first_opened_at' => now()]);
    }

    public static function dispatchEvent(object $event): void
    {
        match (true) {
            $event instanceof ActionPlanSent => self::actionPlanSent($event),
            $event instanceof PicAssigned => self::picAssigned($event),
            $event instanceof FollowUpSubmitted => self::followUpSubmitted($event),
            $event instanceof FollowUpDecided => self::followUpDecided($event),
            $event instanceof FollowUpReturnedToRevision => self::followUpReturned($event),
            $event instanceof IaCommentAdded => self::iaComment($event),
            $event instanceof ProgressReported => self::toManagerOfFollowUp($event->followUp, InboxTaskType::ProgressReported, 'Laporan progres tindak lanjut.'),
            $event instanceof CompletionRequested => self::toManagerOfFollowUp($event->followUp, InboxTaskType::CompletionRequested, 'Menunggu persetujuan penyelesaian tindak lanjut.'),
            $event instanceof CompletionDecided => self::toPicOfFollowUp($event->followUp, InboxTaskType::CompletionDecided, 'Keputusan penyelesaian tindak lanjut.'),
            $event instanceof ActionPlanSubmittedToSpi => self::toAdminSpi($event->actionPlan),
            $event instanceof SpiReviewCompleted => self::spiReviewCompleted($event),
            $event instanceof RevisionForwarded => self::revisionForwarded($event),
            $event instanceof FindingWaitingExternal => self::toKepalaSpi($event->finding, 'Menunggu status auditor eksternal.'),
            $event instanceof ExternalStatusRecorded => self::externalStatus($event),
            $event instanceof FindingClosed => self::findingClosed($event),
            default => null,
        };
    }

    private static function actionPlanSent(ActionPlanSent $event): void
    {
        foreach (self::managerOfDept($event->actionPlan->department_id) as $manager) {
            self::send($manager, InboxTaskType::ActionPlanSent, $event->actionPlan, "Tentukan PIC — {$event->actionPlan->code}", $event->actionPlan->created_by);
        }
    }

    private static function picAssigned(PicAssigned $event): void
    {
        foreach ($event->userIds as $userId) {
            $pic = User::find($userId);
            if ($pic) {
                self::send($pic, InboxTaskType::PicAssigned, $event->actionPlan, "Anda ditunjuk sebagai PIC — {$event->actionPlan->code}", auth()->id());
            }
        }
    }

    private static function followUpSubmitted(FollowUpSubmitted $event): void
    {
        $fu = $event->followUp;
        foreach (self::managerOfDept($fu->actionPlan->department_id) as $manager) {
            self::send($manager, InboxTaskType::FollowUpSubmitted, $fu, "Tindak lanjut #{$fu->id} diajukan ({$fu->description})", $fu->created_by);
        }
    }

    private static function followUpDecided(FollowUpDecided $event): void
    {
        $fu = $event->followUp;
        $title = match ($event->decision) {
            \App\Enums\ReviewDecision::Setujui => 'Tindak lanjut disetujui',
            \App\Enums\ReviewDecision::Revisi => 'Tindak lanjut diminta revisi',
            default => 'Tindak lanjut ditolak',
        };
        self::toPicOfFollowUp($fu, InboxTaskType::FollowUpDecided, $title . " — #{$fu->id}");
    }

    private static function followUpReturned(FollowUpReturnedToRevision $event): void
    {
        self::toPicOfFollowUp($event->followUp, InboxTaskType::RevisionRequested, "Dikembalikan ke revisi — #{$event->followUp->id}");
    }

    private static function iaComment(IaCommentAdded $event): void
    {
        $fu = $event->comment->followUp;
        $title = 'Komentar IA pada tindak lanjut #' . $fu->id;

        foreach (self::managerOfDept($fu->actionPlan->department_id) as $manager) {
            self::send($manager, InboxTaskType::IaComment, $fu, $title, $event->comment->author_id);
        }
        self::toPicOfFollowUp($fu, InboxTaskType::IaComment, $title);
    }

    private static function toManagerOfFollowUp(FollowUp $fu, InboxTaskType $type, string $title): void
    {
        foreach (self::managerOfDept($fu->actionPlan->department_id) as $manager) {
            self::send($manager, $type, $fu, $title, $fu->created_by);
        }
    }

    private static function toPicOfFollowUp(FollowUp $fu, InboxTaskType $type, string $title): void
    {
        $pics = self::picsOfFollowUp($fu);
        foreach ($pics as $pic) {
            self::send($pic, $type, $fu, $title);
        }
    }

    private static function toAdminSpi(ActionPlan $ap): void
    {
        foreach (self::usersByRole(Role::AdminSpi) as $admin) {
            self::send($admin, InboxTaskType::ApSubmittedSpi, $ap, "Action plan {$ap->code} diajukan untuk review SPI", $ap->created_by);
        }
    }

    private static function spiReviewCompleted(SpiReviewCompleted $event): void
    {
        if (!$event->revised) {
            return;
        }
        foreach (self::managerOfDept($event->actionPlan->department_id) as $manager) {
            self::send($manager, InboxTaskType::SpiReviewCompleted, $event->actionPlan, "Review SPI: action plan {$event->actionPlan->code} perlu revisi", auth()->id());
        }
    }

    private static function revisionForwarded(RevisionForwarded $event): void
    {
        foreach ($event->actionPlan->assignees as $pic) {
            $pic = User::find($pic->id);
            if ($pic) {
                self::send($pic, InboxTaskType::RevisionForwarded, $event->actionPlan, "Revisi action plan {$event->actionPlan->code} diteruskan ke PIC", auth()->id());
            }
        }
    }

    private static function toKepalaSpi(Model $subject, string $title): void
    {
        foreach (self::usersByRole(Role::Kepala_spi) as $kepala) {
            self::send($kepala, InboxTaskType::FindingWaitingExternal, $subject, $title, auth()->id());
        }
    }

    private static function externalStatus(ExternalStatusRecorded $event): void
    {
        $title = "Status eksternal {$event->status->value} untuk temuan {$event->finding->registration_number}";
        foreach (self::managersOfFinding($event->finding) as $manager) {
            self::send($manager, InboxTaskType::ExternalStatusRecorded, $event->finding, $title, auth()->id());
        }
    }

    private static function findingClosed(FindingClosed $event): void
    {
        $title = 'Temuan ditutup: ' . ($event->finding->registration_number ?? '#'.$event->finding->id);
        foreach (self::managersOfFinding($event->finding) as $manager) {
            self::send($manager, InboxTaskType::FindingClosed, $event->finding, $title, auth()->id());
        }
        foreach (self::usersByRole(Role::Kepala_spi) as $kepala) {
            self::send($kepala, InboxTaskType::FindingClosed, $event->finding, $title, auth()->id());
        }
        foreach (self::usersByRole(Role::AdminSpi) as $admin) {
            self::send($admin, InboxTaskType::FindingClosed, $event->finding, $title, auth()->id());
        }
    }

    /** Penerima tidak berhak (mis. PIC, temuan sudah CLOSED) → tidak dapat notifikasi. */
    private static function hasAccess(User $recipient, Model $subject): bool
    {
        if ($subject instanceof FollowUp || $subject instanceof ActionPlan || $subject instanceof Finding) {
            $finding = $subject instanceof Finding ? $subject : ($subject->actionPlan ?? null)?->finding ?? null;
            $finding = $finding ?? ($subject instanceof ActionPlan ? $subject->finding : null);
            if ($finding && $finding->status === \App\Enums\FindingStatus::Closed->value
                && $recipient->role === Role::StaffDept) {
                return false;
            }
        }

        return true;
    }

    private static function managerOfDept(int $departmentId): \Illuminate\Support\Collection
    {
        return User::where('role', Role::ManagerDept->value)
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->get();
    }

    private static function managersOfFinding(Finding $finding): \Illuminate\Support\Collection
    {
        $deptIds = ActionPlan::withoutGlobalScopes()->where('finding_id', $finding->id)->pluck('department_id')->unique();

        return User::where('role', Role::ManagerDept->value)
            ->whereIn('department_id', $deptIds)
            ->where('is_active', true)
            ->get();
    }

    private static function picsOfFollowUp(FollowUp $fu): \Illuminate\Support\Collection
    {
        return User::whereIn('id', function ($q) use ($fu) {
            $q->select('user_id')->from('follow_up_assignees')->where('follow_up_id', $fu->id);
        })->get();
    }

    private static function usersByRole(Role $role): \Illuminate\Support\Collection
    {
        return User::where('role', $role->value)->where('is_active', true)->get();
    }
}