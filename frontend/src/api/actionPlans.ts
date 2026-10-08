import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/api/client'
import type { ActionPlan } from '@/types/finding'
import type { AxiosResponse } from 'axios'

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export const actionPlansApi = {
  list: (params?: { page?: number; per_page?: number; status?: string; finding_id?: string }) =>
    apiClient.get<ApiResponse<Paginated<ActionPlan>>>('/action-plans', { params }).then(unwrap),

  get: (id: number) =>
    apiClient.get<ApiResponse<ActionPlan>>(`/action-plans/${id}`).then(unwrap),

  create: (fdId: number, payload: { title: string; description?: string; weight: number }) =>
    apiClient
      .post<ApiResponse<ActionPlan>>(`/finding-departments/${fdId}/action-plans`, payload)
      .then(unwrap),

  update: (id: number, payload: { title?: string; description?: string; weight?: number }) =>
    apiClient
      .put<ApiResponse<ActionPlan>>(`/action-plans/${id}`, payload)
      .then(unwrap),

  delete: (id: number) =>
    apiClient.delete<ApiResponse<null>>(`/action-plans/${id}`).then(unwrap),

  submit: (id: number) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/submit`).then(unwrap),

  approve: (id: number) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/approve`).then(unwrap),

  reject: (id: number, reason: string) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/reject`, { reason }).then(unwrap),

  requestRevision: (id: number, reason: string) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/revision`, { reason }).then(unwrap),

  overrideWeight: (id: number, weight: number) =>
    apiClient.post<ApiResponse<ActionPlan>>(`/action-plans/${id}/override-weight`, { weight }).then(unwrap),

  uploadDocument: (id: number, file: File, label?: string) => {
    const formData = new FormData()
    formData.append('document', file)
    if (label) formData.append('label', label)
    return apiClient
      .post<ApiResponse<any>>(`/action-plans/${id}/documents`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  deleteDocument: (apId: number, docId: number) =>
    apiClient.delete<ApiResponse<null>>(`/action-plans/${apId}/documents/${docId}`).then(unwrap),
}
