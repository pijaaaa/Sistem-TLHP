import { useQuery } from '@tanstack/react-query'
import { auditApi, dashboardApi, type AuditFilters } from '@/api/audit'

export const useDashboard = () =>
  useQuery({
    queryKey: ['dashboard'],
    queryFn: dashboardApi.summary,
    staleTime: 60_000,
  })

export const useAuditTrail = (params?: AuditFilters) =>
  useQuery({
    queryKey: ['audit-trail', params],
    queryFn: () => auditApi.list(params),
  })

export const useAuditActions = () =>
  useQuery({
    queryKey: ['audit-actions'],
    queryFn: auditApi.actions,
    staleTime: 5 * 60_000,
  })
