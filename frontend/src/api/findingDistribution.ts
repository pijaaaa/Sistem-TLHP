import { apiClient } from '@/api/client'
import type { ApiResponse, Paginated } from '@/api/master'
import type { Finding } from '@/types/finding'
import type { FindingDepartment } from '@/types/finding'
import type { AxiosResponse } from 'axios'

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export const findingDistributionApi = {
  pending: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<Finding>>>('/findings/distribution', { params }).then(unwrap),

  distribute: (findingId: number, departmentIds: number[]) =>
    apiClient
      .post<ApiResponse<null>>(`/findings/${findingId}/distribute`, { department_ids: departmentIds })
      .then(unwrap),

  findingDepartments: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<FindingDepartment>>>('/finding-departments', { params }).then(unwrap),

  getFindingDepartment: (id: number) =>
    apiClient.get<ApiResponse<FindingDepartment>>(`/finding-departments/${id}`).then(unwrap),

  assignPics: (findingDepartmentId: number, picIds: number[]) =>
    apiClient
      .post<ApiResponse<null>>(`/finding-departments/${findingDepartmentId}/assign-pics`, { pic_ids: picIds })
      .then(unwrap),
}