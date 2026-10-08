import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/api/client'
import type { AxiosResponse } from 'axios'
import type { Page } from '@/types/finding'
import type { ActionPlan, ActionPlanPayload, ActionPlanUser, DocumentFile, Finding, FindingPayload, Department, FollowUp, FollowUpRowInput, FollowUpReview, FollowUpComment, FollowUpProgressReport } from '@/types/finding'

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export const findingsApi = {
  list: (params?: { page?: number; per_page?: number; status?: string; fiscal_year?: number | string; source?: string; department_id?: number | string; q?: string }) =>
    apiClient.get<ApiResponse<Page<Finding>>>('/findings', { params }).then(unwrap),

  get: (id: number) =>
    apiClient.get<ApiResponse<Finding>>(`/findings/${id}`).then(unwrap),

  create: (payload: FindingPayload) =>
    apiClient.post<ApiResponse<Finding>>('/findings', payload).then(unwrap),

  update: (id: number, payload: FindingPayload) =>
    apiClient.put<ApiResponse<Finding>>(`/findings/${id}`, payload).then(unwrap),

  remove: (id: number) =>
    apiClient.delete<ApiResponse<null>>(`/findings/${id}`).then(unwrap),

  register: (id: number, department_ids: number[]) =>
    apiClient.post<ApiResponse<Finding>>(`/findings/${id}/register`, { department_ids }).then(unwrap),

  activate: (id: number) =>
    apiClient.post<ApiResponse<Finding>>(`/findings/${id}/activate`).then(unwrap),

  documents: (id: number) =>
    apiClient.get<ApiResponse<DocumentFile[]>>(`/findings/${id}/documents`).then(unwrap),

  uploadDocument: (id: number, file: File, label: string) => {
    const form = new FormData()
    form.append('document', file)
    form.append('label', label)
    return apiClient
      .post<ApiResponse<DocumentFile>>(`/findings/${id}/documents`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  deleteDocument: (id: number, documentId: number) =>
    apiClient.delete<ApiResponse<null>>(`/findings/${id}/documents/${documentId}`).then(unwrap),

  downloadDocument: (id: number, documentId: number) =>
    `${apiClient.defaults.baseURL}/findings/${id}/documents/${documentId}/download`,
}

export interface PaginatedActionPlan extends Page<ActionPlan> {}

export const actionPlansApi = {
  list: (params?: { page?: number; per_page?: number; status?: string; finding_id?: number | string; department_id?: number | string }) =>
    apiClient.get<ApiResponse<Page<ActionPlan>>>('/action-plans', { params }).then(unwrap),

  get: (id: number) =>
    apiClient.get<ApiResponse<ActionPlan>>(`/action-plans/${id}`).then(unwrap),

  create: (payload: ActionPlanPayload) =>
    apiClient.post<ApiResponse<ActionPlan[]>>('/action-plans', payload).then(unwrap),

  update: (id: number, payload: Partial<ActionPlanPayload>) =>
    apiClient.put<ApiResponse<ActionPlan>>(`/action-plans/${id}`, payload).then(unwrap),

  remove: (id: number) =>
    apiClient.delete<ApiResponse<null>>(`/action-plans/${id}`).then(unwrap),

  send: (ids: number[]) =>
    apiClient.post<ApiResponse<ActionPlan[]>>('/action-plans/send', { ids }).then(unwrap),

  changeDeadline: (id: number, deadline: string, reason?: string) =>
    apiClient.put<ApiResponse<ActionPlan>>(`/action-plans/${id}/deadline`, { deadline, reason }).then(unwrap),

  assignPics: (id: number, user_ids: number[]) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/assign-pics`, { user_ids }).then(unwrap),

  submitToSpi: (id: number) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/submit-to-spi`).then(unwrap),

  documents: (id: number) =>
    apiClient.get<ApiResponse<DocumentFile[]>>(`/action-plans/${id}/documents`).then(unwrap),

  uploadDocument: (id: number, file: File, label: string) => {
    const form = new FormData()
    form.append('document', file)
    form.append('label', label)
    return apiClient
      .post<ApiResponse<DocumentFile>>(`/action-plans/${id}/documents`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  deleteDocument: (id: number, documentId: number) =>
    apiClient.delete<ApiResponse<null>>(`/action-plans/${id}/documents/${documentId}`).then(unwrap),

  downloadDocument: (id: number, documentId: number) =>
    `${apiClient.defaults.baseURL}/action-plans/${id}/documents/${documentId}/download`,
}

export interface LookupStaff {
  id: number
  name: string
  username: string
}

export const followUpsApi = {
  list: (params?: { page?: number; per_page?: number; status?: string; action_plan_id?: number | string; finding_id?: number | string }) =>
    apiClient.get<ApiResponse<Page<FollowUp>>>('/follow-ups', { params }).then(unwrap),

  get: (id: number) =>
    apiClient.get<ApiResponse<FollowUp>>(`/follow-ups/${id}`).then(unwrap),

  create: (actionPlanId: number, rows: FollowUpRowInput[]) =>
    apiClient.post<ApiResponse<FollowUp[]>>(`/action-plans/${actionPlanId}/follow-ups`, { rows }).then(unwrap),

  update: (id: number, payload: Partial<FollowUpRowInput>) =>
    apiClient.put<ApiResponse<FollowUp>>(`/follow-ups/${id}`, payload).then(unwrap),

  submit: (ids: number[]) =>
    apiClient.post<ApiResponse<FollowUp[]>>('/follow-ups/submit', { ids }).then(unwrap),

  approve: (id: number) =>
    apiClient.post<ApiResponse<FollowUp>>(`/follow-ups/${id}/approve`).then(unwrap),

  requestRevision: (id: number, note: string) =>
    apiClient.post<ApiResponse<FollowUp>>(`/follow-ups/${id}/revision`, { note }).then(unwrap),

  reject: (id: number, note: string) =>
    apiClient.post<ApiResponse<FollowUp>>(`/follow-ups/${id}/reject`, { note }).then(unwrap),

  returnToRevision: (id: number, note: string) =>
    apiClient.post<ApiResponse<FollowUp>>(`/follow-ups/${id}/return-to-revision`, { note }).then(unwrap),

  overrideWeight: (id: number, weight: number) =>
    apiClient.patch<ApiResponse<FollowUp>>(`/follow-ups/${id}/weight`, { weight }).then(unwrap),

  reviews: (id: number) =>
    apiClient.get<ApiResponse<FollowUpReview[]>>(`/follow-ups/${id}/reviews`).then(unwrap),

  comments: (id: number) =>
    apiClient.get<ApiResponse<FollowUpComment[]>>(`/follow-ups/${id}/comments`).then(unwrap),

  addComment: (id: number, kind: string, body: string) =>
    apiClient.post<ApiResponse<FollowUpComment>>(`/follow-ups/${id}/comments`, { kind, body }).then(unwrap),

  approveCompletion: (id: number) =>
    apiClient.post<ApiResponse<FollowUp>>(`/follow-ups/${id}/approve-completion`).then(unwrap),

  completionRevision: (id: number, note: string) =>
    apiClient.post<ApiResponse<FollowUp>>(`/follow-ups/${id}/completion-revision`, { note }).then(unwrap),

  progressReports: (id: number) =>
    apiClient.get<ApiResponse<FollowUpProgressReport[]>>(`/follow-ups/${id}/progress-reports`).then(unwrap),

  reportProgress: (id: number, value: number, note: string | null, files: { file: File; label: string }[]) => {
    const form = new FormData()
    form.append('progress_value', String(value))
    if (note) form.append('note', note)
    files.forEach((f, i) => {
      form.append(`document[${i}][file]`, f.file)
      form.append(`document[${i}][label]`, f.label)
    })
    return apiClient
      .post<ApiResponse<FollowUpProgressReport>>(`/follow-ups/${id}/progress`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  progressReportDownload: (followUpId: number, reportId: number, docId: number) =>
    `${apiClient.defaults.baseURL}/follow-ups/${followUpId}/progress-reports/${reportId}/documents/${docId}/download`,
}

export const lookupsApi = {
  auditeeDepartments: () =>
    apiClient.get<ApiResponse<{ data: Department[] }>>('/lookups/departments', { params: { auditee: 1 } }).then(unwrap),

  staffByDepartment: (departmentId: number) =>
    apiClient.get<ApiResponse<{ data: LookupStaff[] }>>('/lookups/staff', { params: { department_id: departmentId } }).then(unwrap),
}

export { unwrap }
export type { ActionPlanUser }