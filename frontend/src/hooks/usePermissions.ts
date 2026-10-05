import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { accessApi } from '@/api/master'
import type { PermissionMatrix, OverrideMatrix, MenuOption } from '@/types/master'

export const useAccessMenus = () =>
  useQuery({
    queryKey: ['access-menus'],
    queryFn: () => accessApi.list(),
  })

export const useRoleMatrix = (role: string) =>
  useQuery({
    queryKey: ['role-matrix', role],
    queryFn: () => accessApi.roleMatrix(role),
    enabled: !!role,
    select: (data) => ({
      role: data.role,
      role_label: data.role_label,
      menus: data.menus,
      matrix: data.matrix as Record<string, PermissionMatrix>,
    }),
  })

export const useUpdateRolePermissions = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ role, permissions }: { role: string; permissions: Record<string, PermissionMatrix> }) =>
      accessApi.updateRole(role, permissions),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['role-matrix', variables.role] })
    },
  })
}

export const useUserPermissionMatrix = (userId: number) =>
  useQuery({
    queryKey: ['user-permission', userId],
    queryFn: () => accessApi.userMatrix(userId),
    enabled: !!userId,
    select: (data) => ({
      user: data.user,
      role: data.role,
      menus: data.menus as MenuOption[],
      overrides: data.overrides as Record<string, OverrideMatrix>,
      effective: data.effective as Record<string, PermissionMatrix>,
    }),
  })

export const useUpdateUserPermissions = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ userId, permissions }: { userId: number; permissions: Record<string, OverrideMatrix> }) =>
      accessApi.updateUser(userId, permissions),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['user-permission', variables.userId] })
    },
  })
}
