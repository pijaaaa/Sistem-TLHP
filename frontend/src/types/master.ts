export interface MasterItem {
  id: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface Department extends MasterItem {
  code: string
  name: string
}

export interface DepartmentSummary {
  id: number
  code: string
  name: string
  is_active: boolean
}

export interface Employee extends MasterItem {
  nik: string
  name: string
  position?: string | null
  department?: DepartmentSummary
}

export interface AccountUser extends MasterItem {
  name: string
  email: string
  username: string
  role: string
  role_label?: string | null
  department?: DepartmentSummary
  employee?: Employee
}

export type PermissionAction = 'view' | 'create' | 'update' | 'delete'

export interface PermissionMatrix {
  view: boolean
  create: boolean
  update: boolean
  delete: boolean
  [k: string]: boolean
}

export const ROLE_OPTIONS: { value: string; label: string }[] = [
  { value: 'admin_spi', label: 'Admin SPI' },
  { value: 'manager_ia', label: 'Manager IA' },
  { value: 'manager_dept', label: 'Manager Departemen' },
  { value: 'staff_dept', label: 'Staff Departemen / PIC' },
  { value: 'manager_spi', label: 'Manager SPI' },
  { value: 'super_admin', label: 'Super Admin' },
]

export type OverrideMatrixValue = boolean | null

export interface OverrideMatrix {
  view: OverrideMatrixValue
  create: OverrideMatrixValue
  update: OverrideMatrixValue
  delete: OverrideMatrixValue
  [k: string]: OverrideMatrixValue
}

export interface MenuOption {
  id: number
  code: string
  name: string
  parent_id?: number | null
}

export interface RoleOption {
  value: string
  label: string
}
