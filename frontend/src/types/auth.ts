export type Role =
  | 'admin_spi'
  | 'manager_ia'
  | 'manager_dept'
  | 'staff_dept'
  | 'manager_spi'
  | 'super_admin'

export interface Department {
  id: number
  code: string
  name: string
  is_active: boolean
}

export interface User {
  id: number
  name: string
  email: string
  username: string
  role: Role
  department?: Department
  is_active: boolean
  created_at?: string
}

export type Action = 'view' | 'create' | 'update' | 'delete'

export interface Permission {
  view: boolean
  create: boolean
  update: boolean
  delete: boolean
}

export type PermissionsMap = Record<string, Permission>

export interface MenuItem {
  code: string
  name: string
  path: string
  icon: string
  permissions: Permission
  children?: MenuItem[]
}

export interface AuthMe {
  user: User
  role: Role
  department: Department | null
  permissions: PermissionsMap
  menus: MenuItem[]
}
