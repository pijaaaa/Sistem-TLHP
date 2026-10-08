<?php

namespace App\Enums;

enum InboxTaskType: string
{
    case ActionPlanSent = 'ACTION_PLAN_SENT';
    case PicAssigned = 'PIC_ASSIGNED';
    case FollowUpSubmitted = 'FOLLOW_UP_SUBMITTED';
    case FollowUpDecided = 'FOLLOW_UP_DECIDED';
    case RevisionRequested = 'REVISION_REQUESTED';
    case IaComment = 'IA_COMMENT';
    case ProgressReported = 'PROGRESS_REPORTED';
    case CompletionRequested = 'COMPLETION_REQUESTED';
    case CompletionDecided = 'COMPLETION_DECIDED';
    case ApSubmittedSpi = 'AP_SUBMITTED_SPI';
    case SpiReviewCompleted = 'SPI_REVIEW_COMPLETED';
    case RevisionForwarded = 'REVISION_FORWARDED';
    case FindingWaitingExternal = 'FINDING_WAITING_EXTERNAL';
    case ExternalStatusRecorded = 'EXTERNAL_STATUS_RECORDED';
    case FindingClosed = 'FINDING_CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::ActionPlanSent => 'Tentukan PIC',
            self::PicAssigned => 'Ditunjuk sebagai PIC',
            self::FollowUpSubmitted => 'Tindak lanjut diajukan',
            self::FollowUpDecided => 'Keputusan tindak lanjut',
            self::RevisionRequested => 'Kembali ke revisi',
            self::IaComment => 'Komentar IA',
            self::ProgressReported => 'Laporan progres',
            self::CompletionRequested => 'Menunggu persetujuan selesai',
            self::CompletionDecided => 'Keputusan penyelesaian',
            self::ApSubmittedSpi => 'Action plan dikirim ke SPI',
            self::SpiReviewCompleted => 'Hasil review SPI',
            self::RevisionForwarded => 'Revisi diteruskan ke PIC',
            self::FindingWaitingExternal => 'Menunggu status eksternal',
            self::ExternalStatusRecorded => 'Status eksternal dicatat',
            self::FindingClosed => 'Temuan ditutup',
        };
    }
}

enum InboxTaskStatus: string
{
    case Open = 'OPEN';
    case Done = 'DONE';
}