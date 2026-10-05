import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { findingDistributionApi } from '@/api/findingDistribution'
import type { Finding } from '@/types/finding'
import type { FindingDepartment } from '@/types/finding'

export const usePendingDistributions = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['pending-distributions', params],
    queryFn: () => findingDistributionApi.pending(params),
  })

export const useDistributeFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ findingId, departmentIds }: { findingId: number; departmentIds: number[] }) =>
      findingDistributionApi.distribute(findingId, departmentIds),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['pending-distributions'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}

export const useFindingDepartments = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['finding-departments', params],
    queryFn: () => findingDistributionApi.findingDepartments(params),
  })

export const useFindingDepartment = (id: number) =>
  useQuery({
    queryKey: ['finding-departments', id],
    queryFn: () => findingDistributionApi.getFindingDepartment(id),
    enabled: !!id,
  })

export const useAssignPics = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ fdId, picIds }: { fdId: number; picIds: number[] }) =>
      findingDistributionApi.assignPics(fdId, picIds),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['finding-departments'] })
    },
  })
}