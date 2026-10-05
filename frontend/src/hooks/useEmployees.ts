import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { employeesApi, departmentsApi } from '@/api/master'

export const useEmployees = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['employees', params],
    queryFn: () => employeesApi.list(params),
  })

export const useEmployee = (id: number) =>
  useQuery({
    queryKey: ['employees', id],
    queryFn: () => employeesApi.get(id),
    enabled: !!id,
  })

export const useCreateEmployee = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: employeesApi.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  })
}

export const useUpdateEmployee = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; nik: string; name: string; position?: string; department_id: number; is_active?: boolean }) =>
      employeesApi.update(payload.id, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  })
}

export const useDeleteEmployee = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: employeesApi.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['employees'] }),
  })
}

export const useDepartmentsList = () =>
  useQuery({
    queryKey: ['departments-all'],
    queryFn: () => departmentsApi.list({ per_page: 100 }),
    select: (res) => res.data,
  })
