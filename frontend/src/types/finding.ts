export type FindingStatus =
  | 'draft'
  | 'dikirim_ke_ia'
  | 'didistribusikan'
  | 'dalam_proses'
  | 'menunggu_assessment_ia'
  | 'menunggu_verifikasi_spi'
  | 'closed'
  | 'case_closed'

export interface FindingStatusOption {
  value: FindingStatus
  label: string
  variant?: 'default' | 'success' | 'warning' | 'danger' | 'info'
}

export interface Finding {
  id: number
  code: string
  title: string
  finding_date: string | null
  severity: string | null
  status: FindingStatus
  status_label: string
  recommendation: string | null
  auditor_action_plan: string | null
  documents_count: number
  current_round: number
  assessment_status: AssessmentStatus | null
  assessment_status_label: string | null
  assessment_note: string | null
  assessed_by: number | null
  assessed_at: string | null
  progress: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export type AssessmentStatus =
  | 'ssr'
  | 'bsr'
  | 'belum_ditindaklanjuti'
  | 'tidak_dapat_ditindaklanjuti'

export const ASSESSMENT_OPTIONS: {
  value: AssessmentStatus
  label: string
  description: string
  requiresReason: boolean
}[] = [
  {
    value: 'ssr',
    label: 'SSR — Sudah Selesai dan Direkomendasikan',
    description: 'Temuan diteruskan ke Manager SPI untuk verifikasi.',
    requiresReason: false,
  },
  {
    value: 'bsr',
    label: 'BSR — Belum Selesai, Perlu Revisi',
    description: 'Memulai ronde baru; departemen dapat disesuaikan.',
    requiresReason: false,
  },
  {
    value: 'belum_ditindaklanjuti',
    label: 'Belum Ditindaklanjuti',
    description: 'Status default, tidak memicu ronde baru.',
    requiresReason: false,
  },
  {
    value: 'tidak_dapat_ditindaklanjuti',
    label: 'Tidak Dapat Ditindaklanjuti',
    description: 'Menutup kasus (case closed). Alasan wajib diisi.',
    requiresReason: true,
  },
]

export interface FindingDocument {
  id: number
  finding_id: number
  name: string
  mime: string
  size: number
  label: string | null
  download_url: string
  created_at: string
}

export interface FindingForm {
  code: string
  title: string
  finding_date: string
  severity: string
  recommendation: string
  auditor_action_plan: string
  is_active: boolean
}

export const FINDING_STATUS_OPTIONS: FindingStatusOption[] = [
  { value: 'draft', label: 'Draft', variant: 'default' },
  { value: 'dikirim_ke_ia', label: 'Dikirim ke IA', variant: 'info' },
  { value: 'didistribusikan', label: 'Didistribusikan', variant: 'info' },
  { value: 'dalam_proses', label: 'Dalam Proses', variant: 'info' },
  { value: 'menunggu_assessment_ia', label: 'Menunggu Assessment IA', variant: 'warning' },
  { value: 'menunggu_verifikasi_spi', label: 'Menunggu Verifikasi SPI', variant: 'info' },
  { value: 'closed', label: 'Closed', variant: 'success' },
  { value: 'case_closed', label: 'Tidak Dapat Ditindaklanjuti', variant: 'danger' },
]

export const FINDING_SEVERITY_OPTIONS: { value: string; label: string }[] = [
  { value: 'critical', label: 'Kritis' },
  { value: 'high', label: 'Tinggi' },
  { value: 'medium', label: 'Sedang' },
  { value: 'low', label: 'Rendah' },
]

export const getFindingStatusLabel = (status: FindingStatus | string): string => {
  const option = FINDING_STATUS_OPTIONS.find((o) => o.value === status)
  return option?.label ?? status
}

export const getFindingStatusVariant = (status: FindingStatus | string): string => {
  return FINDING_STATUS_OPTIONS.find((o) => o.value === status)?.variant ?? 'default'
}

export type FindingDepartmentStatus =
  | 'diterima'
  | 'pic_ditugaskan'
  | 'dalam_proses'
  | 'selesai_100'
  | 'diteruskan_ke_ia'

export interface FindingDepartmentPic {
  id: number
  name: string
  email: string
  username: string
  role: string
  role_label: string | null
}

export interface FindingDepartment {
  id: number
  finding_id: number
  finding?: Finding
  department_id: number
  department?: { id: number; code: string; name: string; is_active: boolean }
  status: FindingDepartmentStatus
  status_label: string
  assigned_by: number | null
  progress: number
  pics: FindingDepartmentPic[]
  created_at: string
  updated_at: string
}

export const FINDING_DEPARTMENT_STATUS_OPTIONS: { value: FindingDepartmentStatus; label: string }[] = [
  { value: 'diterima', label: 'Diterima' },
  { value: 'pic_ditugaskan', label: 'PIC Di-tugaskan' },
  { value: 'dalam_proses', label: 'Dalam Proses' },
  { value: 'selesai_100', label: 'Selesai 100%' },
  { value: 'diteruskan_ke_ia', label: 'Diteruskan ke IA' },
]

export const getFindingDepartmentStatusLabel = (status: FindingDepartmentStatus | string): string => {
  return FINDING_DEPARTMENT_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? status
}

export type ActionPlanStatus =
  | 'draft'
  | 'diajukan'
  | 'disetujui'
  | 'ditolak'
  | 'revisi'
  | 'menunggu_evidence'
  | 'evidence_diajukan'
  | 'evidence_disetujui'
  | 'evidence_revisi'

export interface ActionPlanUser {
  id: number
  name: string
  email: string
  username: string
  role: string
  role_label: string | null
}

export interface ActionPlanDocument {
  id: number
  action_plan_id: number
  name: string
  mime: string
  size: number
  label: string | null
  download_url: string
  created_at: string
  updated_at: string
}

export interface ActionPlan {
  id: number
  finding_department_id: number
  finding_department?: FindingDepartment
  title: string
  description: string | null
  weight: string
  status: ActionPlanStatus
  status_label: string
  created_by: number
  creator?: ActionPlanUser
  approved_by: number | null
  approver?: ActionPlanUser
  approved_at: string | null
  rejection_reason: string | null
  due_date: string | null
  documents_count: number
  latest_evidence?: EvidenceSubmission | null
  created_at: string
  updated_at: string
}

export interface ActionPlanForm {
  title: string
  description: string
  weight: string
  due_date: string
}

export const ACTION_PLAN_STATUS_OPTIONS: { value: ActionPlanStatus; label: string; variant: string }[] = [
  { value: 'draft', label: 'Draft', variant: 'default' },
  { value: 'diajukan', label: 'Diajukan', variant: 'info' },
  { value: 'disetujui', label: 'Disetujui', variant: 'success' },
  { value: 'ditolak', label: 'Ditolak', variant: 'danger' },
  { value: 'revisi', label: 'Revisi', variant: 'warning' },
  { value: 'menunggu_evidence', label: 'Menunggu Evidence', variant: 'info' },
  { value: 'evidence_diajukan', label: 'Evidence Diajukan', variant: 'info' },
  { value: 'evidence_disetujui', label: 'Evidence Disetujui', variant: 'success' },
  { value: 'evidence_revisi', label: 'Evidence Revisi', variant: 'warning' },
]

export const getActionPlanStatusLabel = (status: ActionPlanStatus | string): string => {
  return ACTION_PLAN_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? status
}

export const getActionPlanStatusVariant = (status: ActionPlanStatus | string): string => {
  return ACTION_PLAN_STATUS_OPTIONS.find((o) => o.value === status)?.variant ?? 'default'
}

export type EvidenceStatus = 'diajukan' | 'disetujui' | 'revisi'

export interface EvidenceFile {
  id: number
  evidence_submission_id: number
  name: string
  mime: string
  size: number
  label: string | null
  download_url: string
  created_at: string
}

export interface EvidenceSubmission {
  id: number
  action_plan_id: number
  status: EvidenceStatus
  status_label: string
  submitted_by: number | null
  reviewed_by: number | null
  reviewed_at: string | null
  revision_note: string | null
  files: EvidenceFile[]
  created_at: string
  updated_at: string
}

export const getEvidenceStatusLabel = (status: EvidenceStatus | string): string => {
  const map: Record<string, string> = {
    diajukan: 'Diajukan',
    disetujui: 'Disetujui',
    revisi: 'Revisi',
  }
  return map[status] ?? status
}

export interface DepartmentProgress {
  finding_department_id: number
  progress: number
  status: FindingDepartmentStatus
  status_label: string
}
