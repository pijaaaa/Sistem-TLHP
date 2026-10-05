import { useAuth } from '@/contexts/AuthContext'
import type { Action, Permission } from '@/types/auth'

export const usePermission = (menuCode: string): Permission => {
  const { permissions } = useAuth()
  return permissions[menuCode] ?? { view: false, create: false, update: false, delete: false }
}

export const can = (permission: Permission | undefined, action: Action): boolean => {
  return permission ? permission[action] : false
}

export const useCan = (menuCode: string, action: Action): boolean => {
  const p = usePermission(menuCode)
  return can(p, action)
}
