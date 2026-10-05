import { apiClient } from '@/api/client'
import type { ApiResponse, Paginated } from '@/api/master'
import type { ActionPlan, ActionPlanDocument, ActionPlanForm } from '@/types/finding'
import type { AxiosResponse } from 'axios'

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export const actionPlansApi = {
  list: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<ActionPlan>>>('/action-plans', { params }).then(unwrap),

  get: (id: number) => apiClient.get<ApiResponse<ActionPlan>>(`/action-plans/${id}`).then(unwrap),

  create: (findingDepartmentId: number, payload: ActionPlanForm) =>
    apiClient
      .post<ApiResponse<ActionPlan>>(`/finding-departments/${findingDepartmentId}/action-plans`, payload)
      .then(unwrap),

  update: (id: number, payload: ActionPlanForm) =>
    apiClient.put<ApiResponse<ActionPlan>>(`/action-plans/${id}`, payload).then(unwrap),

  remove: (id: number) => apiClient.delete<ApiResponse<null>>(`/action-plans/${id}`).then(unwrap),

  submit: (id: number) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/submit`).then(unwrap),

  documents: (actionPlanId: number) =>
    apiClient.get<ApiResponse<ActionPlanDocument[]>>(`/action-plans/${actionPlanId}/documents`).then(unwrap),

  uploadDocument: (actionPlanId: number, payload: { document: File; label?: string | null }) => {
    const form = new FormData()
    form.append('document', payload.document)
    if (payload.label) form.append('label', payload.label)
    return apiClient
      .post<ApiResponse<ActionPlanDocument>>(`/action-plans/${actionPlanId}/documents`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  deleteDocument: (actionPlanId: number, documentId: number) =>
    apiClient
      .delete<ApiResponse<null>>(`/action-plans/${actionPlanId}/documents/${documentId}`)
      .then(unwrap),

  downloadDocument: (documentId: number) =>
    `${apiClient.defaults.baseURL}/action-plan-documents/${documentId}/download`,
}
