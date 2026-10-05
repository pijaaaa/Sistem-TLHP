export type FindingStatus =
  | 'draft'
  | 'dikirim_ke_ia'
  | 'didistribusikan'
  | 'dalam_proses'
  | 'menunggu_assessment_ia'
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
  is_active: boolean
  created_at: string
  updated_at: string
}

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
