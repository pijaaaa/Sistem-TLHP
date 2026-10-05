<?php

namespace App\Enums;

enum ActionPlanStatus: string
{
    case Draft = 'draft';
    case Submitted = 'diajukan';
    case Approved = 'disetujui';
    case Rejected = 'ditolak';
    case Revision = 'revisi';
    case WaitingEvidence = 'menunggu_evidence';
    case EvidenceSubmitted = 'evidence_diajukan';
    case EvidenceApproved = 'evidence_disetujui';
    case EvidenceRevision = 'evidence_revisi';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Diajukan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Revision => 'Revisi',
            self::WaitingEvidence => 'Menunggu Evidence',
            self::EvidenceSubmitted => 'Evidence Diajukan',
            self::EvidenceApproved => 'Evidence Disetujui',
            self::EvidenceRevision => 'Evidence Revisi',
        };
    }

    public function isEditableByPic(): bool
    {
        return in_array($this, [self::Draft, self::Revision], true);
    }

    public function isSubmittedOrLater(): bool
    {
        $submittedOrLater = [
            self::Submitted, self::Approved, self::Rejected, self::Revision,
            self::WaitingEvidence, self::EvidenceSubmitted, self::EvidenceApproved, self::EvidenceRevision,
        ];
        return in_array($this, $submittedOrLater, true);
    }

    public function isActive(): bool
    {
        return $this !== self::Rejected;
    }
}
