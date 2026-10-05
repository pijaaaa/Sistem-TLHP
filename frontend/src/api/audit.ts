import { apiClient } from '@/api/client'
import type { ApiResponse, Paginated } from '@/api/master'
import type { AxiosResponse } from 'axios'

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export interface Audit {
  id: number
  action: string
  entity_type: string | null
  entity_id: number | null
  user_id: number | null
  user?: { id: number; name: string; username: string } | null
  ip_address: string | null
  description: string | null
  payload: Record<string, unknown> | null
  created_at: string
}

export interface AuditFilters {
  page?: number
  per_page?: number
  action?: string
  entity_type?: string
  entity_id?: number
  user_id?: number
  from?: string
  to?: string
}

export const auditApi = {
  list: (params?: AuditFilters) =>
    apiClient.get<ApiResponse<Paginated<Audit>>>('/audit-trail', { params }).then(unwrap),

  actions: () => apiClient.get<ApiResponse<string[]>>('/audit-trail/actions').then(unwrap),
}

export const dashboardApi = {
  summary: () =>
    apiClient
      .get<ApiResponse<{
        role: string
        role_label: string
        counters: Record<string, number>
        findings_by_status: Record<string, number>
      }>>('/dashboard')
      .then(unwrap),
}

export const exportApi = {
  url: {
    findings: `${apiClient.defaults.baseURL}/exports/findings`,
    actionPlans: `${apiClient.defaults.baseURL}/exports/action-plans`,
    auditTrail: `${apiClient.defaults.baseURL}/exports/audit-trail`,
  },
}