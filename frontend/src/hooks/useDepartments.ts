import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { departmentsApi } from '@/api/master'

export const useDepartments = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['departments', params],
    queryFn: () => departmentsApi.list(params),
  })

export const useDepartment = (id: number) =>
  useQuery({
    queryKey: ['departments', id],
    queryFn: () => departmentsApi.get(id),
    enabled: !!id,
  })

export const useCreateDepartment = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: departmentsApi.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['departments'], exact: false }),
  })
}

export const useUpdateDepartment = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; code: string; name: string; is_active?: boolean }) =>
      departmentsApi.update(payload.id, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['departments'], exact: false }),
  })
}

export const useDeleteDepartment = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: departmentsApi.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['departments'], exact: false }),
  })
}
