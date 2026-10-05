import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { apiClient, setAuthLogout, fetchAuthMe } from '@/api/client'
import type { AuthMe, User } from '@/types/auth'

interface AuthState {
  user: User | null
  role: AuthMe['role'] | null
  department: AuthMe['department'] | null
  permissions: AuthMe['permissions']
  menus: AuthMe['menus']
  isReady: boolean
}

interface AuthContextValue extends AuthState {
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  refreshMe: () => Promise<void>
}

const TOKEN_KEY = 'auth_token'

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null)
  const [role, setRole] = useState<AuthMe['role'] | null>(null)
  const [department, setDepartment] = useState<AuthMe['department'] | null>(null)
  const [permissions, setPermissions] = useState<AuthMe['permissions']>({})
  const [menus, setMenus] = useState<AuthMe['menus']>([])
  const [isReady, setIsReady] = useState(false)

  const loadMe = useCallback(async () => {
    try {
      const me = await fetchAuthMe()
      setUser(me.user)
      setRole(me.role)
      setDepartment(me.department)
      setPermissions(me.permissions)
      setMenus(me.menus)
    } catch {
      setUser(null)
      setRole(null)
      setDepartment(null)
      setPermissions({})
      setMenus([])
    } finally {
      setIsReady(true)
    }
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const { data } = await apiClient.post<{ data: { user: User; token: string } }>('/auth/login', {
      email,
      password,
    })
    const token = data.data.token
    localStorage.setItem(TOKEN_KEY, token)
    apiClient.defaults.headers.common.Authorization = `Bearer ${token}`
    await loadMe()
  }, [loadMe])

  const logout = useCallback(async () => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      try {
        await apiClient.post('/auth/logout')
      } catch {
        // ignore
      }
    }
    localStorage.removeItem(TOKEN_KEY)
    apiClient.defaults.headers.common.Authorization = ''
    setUser(null)
    setRole(null)
    setDepartment(null)
    setPermissions({})
    setMenus([])
    setIsReady(true)
  }, [])

  const refreshMe = useCallback(async () => {
    await loadMe()
  }, [loadMe])

  // Auto attach Authorization header on first render if token exists
  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      apiClient.defaults.headers.common.Authorization = `Bearer ${token}`
    }
  }, [])

  // On mount, if token exists, fetch /me; otherwise mark ready
  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      loadMe()
    } else {
      setIsReady(true)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Register logout callback for 401 responses
  useEffect(() => {
    setAuthLogout(() => {
      logout()
    })
  }, [logout])

  return (
    <AuthContext.Provider
      value={{
        user,
        role,
        department,
        permissions,
        menus,
        isReady,
        login,
        logout,
        refreshMe,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return ctx
}
