import { type ReactNode } from 'react'
import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'
import type { Action } from '@/types/auth'
import { usePermission } from '@/hooks/usePermission'

interface ProtectedRouteProps {
  fallback?: ReactNode
}

const ProtectedRoute = ({ fallback }: ProtectedRouteProps) => {
  const { user, isReady } = useAuth()
  const location = useLocation()

  if (!isReady) {
    return fallback ?? <div className="flex h-screen items-center justify-center">Memuat...</div>
  }

  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return <Outlet />
}

interface PermissionRouteProps {
  menu: string
  action: Action
  children: ReactNode
  fallback?: ReactNode
}

const PermissionRoute = ({ menu, action, children, fallback }: PermissionRouteProps) => {
  const { isReady } = useAuth()
  const perm = usePermission(menu)

  if (!isReady) {
    return fallback ?? <div className="flex h-screen items-center justify-center">Memuat...</div>
  }

  if (!perm[action]) {
    return <Navigate to="/403" replace />
  }

  return <>{children}</>
}

export { ProtectedRoute, PermissionRoute }
