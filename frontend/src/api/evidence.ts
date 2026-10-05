import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/api/client'
import type { EvidenceFile, EvidenceSubmission, DepartmentProgress } from '@/types/finding'
import type { AxiosResponse } from 'axios'

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export interface EvidenceUploadItem {
  file: File
  label?: string | null
}

export const evidenceApi = {
  list: (actionPlanId: number) =>
    apiClient
      .get<ApiResponse<EvidenceSubmission[]>>(`/action-plans/${actionPlanId}/evidence`)
      .then(unwrap),

  submit: (actionPlanId: number, files: EvidenceUploadItem[]) => {
    const form = new FormData()
    files.forEach((f, i) => {
      form.append(`files[${i}][file]`, f.file)
      if (f.label) form.append(`files[${i}][label]`, f.label)
    })
    return apiClient
      .post<ApiResponse<EvidenceSubmission>>(`/action-plans/${actionPlanId}/evidence`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then(unwrap)
  },

  approve: (actionPlanId: number) =>
    apiClient.post<ApiResponse<unknown>>(`/action-plans/${actionPlanId}/evidence/approve`).then(unwrap),

  requestRevision: (actionPlanId: number, note: string) =>
    apiClient
      .post<ApiResponse<unknown>>(`/action-plans/${actionPlanId}/evidence/revision`, { note })
      .then(unwrap),

  progress: (findingDepartmentId: number) =>
    apiClient
      .get<ApiResponse<DepartmentProgress>>(`/finding-departments/${findingDepartmentId}/progress`)
      .then(unwrap),

  downloadFile: (fileId: number) =>
    `${apiClient.defaults.baseURL}/evidence-files/${fileId}/download`,
}

export const evidenceFileApi = { list: (file: EvidenceFile) => file.download_url }
