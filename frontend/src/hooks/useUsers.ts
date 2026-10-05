import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { usersApi } from '@/api/master'

export const useUsers = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['users', params],
    queryFn: () => usersApi.list(params),
  })

export const useUserAccount = (id: number) =>
  useQuery({
    queryKey: ['users', id],
    queryFn: () => usersApi.get(id),
    enabled: !!id,
  })

export const useCreateUser = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: usersApi.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['users'] }),
  })
}

export const useUpdateUser = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: {
      id: number
      name: string
      email: string
      username: string
      role: string
      department_id?: number
      employee_id?: number
      is_active?: boolean
      password?: string
      password_confirmation?: string
    }) => usersApi.update(payload.id, payload),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['users'] }),
  })
}

export const useDeleteUser = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: usersApi.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['users'] }),
  })
}
