export type FindingStatus =
  | 'DRAFT'
  | 'TERDAFTAR'
  | 'PROSES_TINDAK_LANJUT'
  | 'REVIEW_SPI'
  | 'MENUNGGU_STATUS_EKSTERNAL'
  | 'CLOSED'

export type ActionPlanStatus =
  | 'DRAFT'
  | 'MENUNGGU_PENENTUAN_PIC'
  | 'PROSES_TINDAK_LANJUT'
  | 'DIAJUKAN_KE_SPI'
  | 'REVISI_SPI'
  | 'SESUAI'
  | 'CLOSED'

export type RiskLevel = 'RENDAH' | 'SEDANG' | 'TINGGI' | 'KRITIS'
export type FindingSource = 'BPK' | 'BPKP' | 'KAP' | 'LAINNYA'

export type FollowUpStatus =
  | 'DRAFT'
  | 'DIAJUKAN'
  | 'REVISI'
  | 'DITOLAK'
  | 'DISETUJUI'
  | 'MENUNGGU_PERSETUJUAN_SELESAI'
  | 'SELESAI'

export interface Department {
  id: number
  code: string
  name: string
  is_active: boolean
}

export interface DocumentFile {
  id: number
  label: string
  name: string
  mime: string
  size: number
  uploaded_by: number | null
  created_at: string
}

export interface Finding {
  id: number
  registration_number: string | null
  source: FindingSource | null
  source_name: string | null
  lhp_number: string | null
  lhp_date: string | null
  finding_date: string | null
  response_period_start: string | null
  response_period_end: string | null
  fiscal_year: number | null
  scope: string | null
  title: string
  status: FindingStatus
  status_label: string
  age_days: number
  progress: number | null
  activated_at: string | null
  closed_at: string | null
  closed_by: number | null
  created_by: number | null
  documents_count: number
  auditee_departments?: Department[]
  action_plans_count?: number | null
  created_at: string
  updated_at: string
}

export type FindingPayload = Partial<{
  title: string
  source: FindingSource | string
  source_name: string | null
  lhp_number: string | null
  lhp_date: string | null
  finding_date: string | null
  response_period_start: string | null
  response_period_end: string | null
  scope: string | null
}>

export interface ActionPlanUser {
  id: number
  name: string
  email: string
  username: string
  role: string
  role_label: string | null
  department?: Department
}

export interface ActionPlan {
  id: number
  finding_id: number
  department_id: number
  code: string
  title: string
  condition: string | null
  criteria: string | null
  cause: string | null
  impact: string | null
  risk: RiskLevel | null
  risk_label: string | null
  deadline: string | null
  loss_idr: string | number | null
  loss_usd: string | number | null
  status: ActionPlanStatus
  status_label: string
  current_revision: number
  progress: number
  sent_at: string | null
  can_edit: boolean
  finding?: Finding
  department?: Department
  assignees?: ActionPlanUser[]
  documents?: DocumentFile[]
  created_at: string
  updated_at: string
}

export interface ActionPlanPayload {
  finding_id: number
  department_ids: number[]
  title: string
  condition?: string | null
  criteria?: string | null
  cause?: string | null
  impact?: string | null
  risk?: RiskLevel | string | null
  deadline?: string | null
  deadline_per_department?: Record<number, string | null>
  loss_idr?: number | null
  loss_usd?: number | null
}

export interface Page<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export const FINDING_STATUS_OPTIONS: { value: FindingStatus; label: string; variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }[] = [
  { value: 'DRAFT', label: 'Draft', variant: 'warning' },
  { value: 'TERDAFTAR', label: 'Terdaftar', variant: 'info' },
  { value: 'PROSES_TINDAK_LANJUT', label: 'Proses Tindak Lanjut', variant: 'info' },
  { value: 'REVIEW_SPI', label: 'Review SPI', variant: 'warning' },
  { value: 'MENUNGGU_STATUS_EKSTERNAL', label: 'Menunggu Status Eksternal', variant: 'warning' },
  { value: 'CLOSED', label: 'Closed', variant: 'success' },
]

export const ACTION_PLAN_STATUS_OPTIONS: { value: ActionPlanStatus; label: string; variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }[] = [
  { value: 'DRAFT', label: 'Draft', variant: 'warning' },
  { value: 'MENUNGGU_PENENTUAN_PIC', label: 'Menunggu Penentuan PIC', variant: 'info' },
  { value: 'PROSES_TINDAK_LANJUT', label: 'Proses Tindak Lanjut', variant: 'info' },
  { value: 'DIAJUKAN_KE_SPI', label: 'Diajukan ke SPI', variant: 'info' },
  { value: 'REVISI_SPI', label: 'Revisi SPI', variant: 'warning' },
  { value: 'SESUAI', label: 'Sesuai', variant: 'success' },
  { value: 'CLOSED', label: 'Closed', variant: 'success' },
]

export interface FollowUp {
  id: number
  action_plan_id: number
  revision_no: number
  description: string
  target_date: string
  weight: number
  progress: number
  status: FollowUpStatus
  status_label: string
  linked_follow_up_id: number | null
  created_by: number | null
  approved_by: number | null
  approved_at: string | null
  completed_at: string | null
  assignees?: ActionPlanUser[]
  action_plan?: ActionPlan
  created_at: string
  updated_at: string
}

export interface FollowUpRowInput {
  description: string
  target_date: string
  weight: number | ''
  pic_ids: number[]
  linked_follow_up_id?: number | null
}

export type ReviewDecision = 'SETUJUI' | 'REVISI' | 'TOLAK' | 'KEMBALI_REVISI' | 'OVERRIDE_BOBOT' | 'SELESAI' | 'REVISI_SELESAI'

export interface FollowUpReview {
  id: number
  follow_up_id: number
  reviewer_id: number
  reviewer?: { id: number; name: string; role: string }
  decision: ReviewDecision
  decision_label: string
  note: string | null
  weight_before: number
  weight_after: number
  created_at: string
}

export type CommentKind = 'DISKUSI' | 'IA_COMMENT'

export interface FollowUpComment {
  id: number
  follow_up_id: number
  author_id: number
  author?: { id: number; name: string; role: string }
  kind: CommentKind
  kind_label: string
  body: string
  created_at: string
}

export interface FollowUpProgressReport {
  id: number
  follow_up_id: number
  progress_value: number
  note: string | null
  reported_by: number | null
  reporter?: { id: number; name: string } | null
  reported_at: string
  documents: DocumentFile[]
}

export const FOLLOW_UP_STATUS_OPTIONS: { value: FollowUpStatus; label: string; variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }[] = [
  { value: 'DRAFT', label: 'Draft', variant: 'warning' },
  { value: 'DIAJUKAN', label: 'Diajukan', variant: 'info' },
  { value: 'REVISI', label: 'Revisi', variant: 'warning' },
  { value: 'DITOLAK', label: 'Ditolak', variant: 'danger' },
  { value: 'DISETUJUI', label: 'Disetujui', variant: 'success' },
  { value: 'MENUNGGU_PERSETUJUAN_SELESAI', label: 'Menunggu Persetujuan Selesai', variant: 'info' },
  { value: 'SELESAI', label: 'Selesai', variant: 'success' },
]

export const getFollowUpStatusVariant = (status?: string | null) =>
  FOLLOW_UP_STATUS_OPTIONS.find((o) => o.value === status)?.variant ?? 'default'

export const FOLLOW_UP_STATUS_LABEL: Record<string, string> = Object.fromEntries(
  FOLLOW_UP_STATUS_OPTIONS.map((o) => [o.value, o.label]),
)

export const getFollowUpStatusLabel = (status?: string | null): string =>
  FOLLOW_UP_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? status ?? '-'

export const RISK_OPTIONS: { value: RiskLevel; label: string; variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }[] = [
  { value: 'RENDAH', label: 'Rendah', variant: 'success' },
  { value: 'SEDANG', label: 'Sedang', variant: 'info' },
  { value: 'TINGGI', label: 'Tinggi', variant: 'warning' },
  { value: 'KRITIS', label: 'Kritis', variant: 'danger' },
]

export const SOURCE_OPTIONS: { value: FindingSource; label: string }[] = [
  { value: 'BPK', label: 'BPK' },
  { value: 'BPKP', label: 'BPKP' },
  { value: 'KAP', label: 'KAP' },
  { value: 'LAINNYA', label: 'Lainnya' },
]

export const getFindingStatusLabel = (status?: string | null): string =>
  FINDING_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? status ?? '-'

export const getFindingStatusVariant = (status?: string | null) =>
  FINDING_STATUS_OPTIONS.find((o) => o.value === status)?.variant ?? 'default'

export const getActionPlanStatusLabel = (status?: string | null): string =>
  ACTION_PLAN_STATUS_OPTIONS.find((o) => o.value === status)?.label ?? status ?? '-'

export const getActionPlanStatusVariant = (status?: string | null) =>
  ACTION_PLAN_STATUS_OPTIONS.find((o) => o.value === status)?.variant ?? 'default'