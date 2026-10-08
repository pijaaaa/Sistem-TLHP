import { useQuery } from '@tanstack/react-query'
import { reportsApi, findingsApi, type ReportFilters } from '@/api/findings'

export const useReportDepartments = (params?: ReportFilters) =>
  useQuery({
    queryKey: ['reports', 'departments', params],
    queryFn: () => reportsApi.departments(params),
    select: (r) => r.data,
  })

export const useReportLate = (params?: ReportFilters) =>
  useQuery({
    queryKey: ['reports', 'late', params],
    queryFn: () => reportsApi.late(params),
    select: (r) => r.data,
  })

export const useReportAge = (params?: ReportFilters) =>
  useQuery({
    queryKey: ['reports', 'age', params],
    queryFn: () => reportsApi.age(params),
    select: (r) => r.data,
  })

export const useReportRisk = (params?: ReportFilters) =>
  useQuery({
    queryKey: ['reports', 'risk', params],
    queryFn: () => reportsApi.risk(params),
    select: (r) => r.data,
  })

export const useFindingTree = (findingId: number) =>
  useQuery({
    queryKey: ['findings', 'tree', findingId],
    queryFn: () => findingsApi.tree(findingId),
    enabled: !!findingId,
  })