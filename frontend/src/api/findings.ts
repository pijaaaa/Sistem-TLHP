import { apiClient } from '@/api/client'
import type { ApiResponse, Paginated } from '@/api/master'
import type { Finding, FindingDocument, FindingVerification } from '@/types/finding'
import type { AxiosResponse } from 'axios'

export interface VerificationResult {
  verification: FindingVerification
  finding: Finding
}

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export interface FindingPayload {
  code: string
  title: string
  finding_date?: string | null
  severity?: string | null
  recommendation?: string | null
  auditor_action_plan?: string | null
  is_active?: boolean
}

export interface FindingDocumentPayload {
  document: File
  label?: string | null
}

export const findingsApi = {
  list: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<Finding>>>('/findings', { params }).then(unwrap),

  get: (id: number) =>
    apiClient.get<ApiResponse<Finding>>(`/findings/${id}`).then(unwrap),

  create: (payload: FindingPayload) =>
    apiClient.post<ApiResponse<Finding>>('/findings', payload).then(unwrap),

  update: (id: number, payload: FindingPayload) =>
    apiClient.put<ApiResponse<Finding>>(`/findings/${id}`, payload).then(unwrap),

  remove: (id: number) =>
    apiClient.delete<ApiResponse<null>>(`/findings/${id}`).then(unwrap),

  sendToIA: (id: number) =>
    apiClient.post<ApiResponse<Finding>>(`/findings/${id}/send-to-ia`).then(unwrap),

  pendingAssessment: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<Finding>>>('/assessments', { params }).then(unwrap),

  assess: (
    id: number,
    payload: { assessment_status: string; note?: string | null; department_ids?: number[] },
  ) =>
    apiClient
      .post<ApiResponse<Finding>>(`/findings/${id}/assess`, payload)
      .then(unwrap),

  pendingVerifications: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<Finding>>>('/verifications', { params }).then(unwrap),

  recordVerification: (
    id: number,
    payload: {
      auditor_conclusion: string
      auditor_result?: string | null
      verified_date?: string | null
      notes?: string | null
    },
  ) =>
    apiClient.post<ApiResponse<VerificationResult>>(`/findings/${id}/verifications`, payload).then(unwrap),

  verifications: (id: number) =>
    apiClient
      .get<ApiResponse<FindingVerification[]>>(`/findings/${id}/verifications`)
      .then(unwrap),

  documents: (findingId: number) =>
    apiClient.get<ApiResponse<FindingDocument[]>>(`/findings/${findingId}/documents`).then(unwrap),

  uploadDocument: (findingId: number, payload: FindingDocumentPayload) => {
    const form = new FormData()
    form.append('document', payload.document)
    if (payload.label) form.append('label', payload.label)
    return apiClient
      .post<ApiResponse<FindingDocument>>(`/findings/${findingId}/documents`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  deleteDocument: (findingId: number, documentId: number) =>
    apiClient.delete<ApiResponse<null>>(`/findings/${findingId}/documents/${documentId}`).then(unwrap),

  downloadDocument: (documentId: number) =>
    `${apiClient.defaults.baseURL}/findings/documents/${documentId}/download`,
}
