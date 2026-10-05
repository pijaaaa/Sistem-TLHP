import { type ReactNode } from 'react'
import { usePermission } from '@/hooks/usePermission'
import type { Action } from '@/types/auth'

interface CanProps {
  menu: string
  action: Action
  children: ReactNode
  fallback?: ReactNode
}

const Can = ({ menu, action, children, fallback = null }: CanProps) => {
  const perm = usePermission(menu)
  return perm[action] ? <>{children}</> : <>{fallback}</>
}

export { Can }
