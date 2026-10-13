import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { findingSourcesApi } from '@/api/master'

const invalidate = (qc: ReturnType<typeof useQueryClient>) => {
  qc.invalidateQueries({ queryKey: ['finding-sources'], exact: false })
  qc.invalidateQueries({ queryKey: ['lookups', 'finding-sources'] })
}

export const useMasterFindingSources = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['finding-sources', params],
    queryFn: () => findingSourcesApi.list(params),
  })

export const useCreateFindingSource = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: findingSourcesApi.create,
    onSuccess: () => invalidate(qc),
  })
}

export const useUpdateFindingSource = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; code: string; name: string; is_active?: boolean }) =>
      findingSourcesApi.update(payload.id, payload),
    onSuccess: () => invalidate(qc),
  })
}

export const useDeleteFindingSource = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: findingSourcesApi.remove,
    onSuccess: () => invalidate(qc),
  })
}
