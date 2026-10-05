import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/api/client'
import type {
  AccountUser,
  Department,
  Employee,
  MenuOption,
  PermissionMatrix,
  RoleOption,
} from '@/types/master'
import type { AxiosResponse } from 'axios'

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

function unwrap<T>(res: AxiosResponse<ApiResponse<T>>): T {
  return res.data.data
}

export const departmentsApi = {
  list: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<Department>>>('/master/departments', { params }).then(unwrap),
  get: (id: number) => apiClient.get<ApiResponse<Department>>(`/master/departments/${id}`).then(unwrap),
  create: (payload: { code: string; name: string; is_active?: boolean }) =>
    apiClient.post<ApiResponse<Department>>('/master/departments', payload).then(unwrap),
  update: (id: number, payload: { code: string; name: string; is_active?: boolean }) =>
    apiClient.put<ApiResponse<Department>>(`/master/departments/${id}`, payload).then(unwrap),
  remove: (id: number) => apiClient.delete<ApiResponse<null>>(`/master/departments/${id}`).then(unwrap),
}

export const employeesApi = {
  list: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<Employee>>>('/master/employees', { params }).then(unwrap),
  get: (id: number) => apiClient.get<ApiResponse<Employee>>(`/master/employees/${id}`).then(unwrap),
  create: (payload: { nik: string; name: string; position?: string; department_id: number; is_active?: boolean }) =>
    apiClient.post<ApiResponse<Employee>>('/master/employees', payload).then(unwrap),
  update: (id: number, payload: { nik: string; name: string; position?: string; department_id: number; is_active?: boolean }) =>
    apiClient.put<ApiResponse<Employee>>(`/master/employees/${id}`, payload).then(unwrap),
  remove: (id: number) => apiClient.delete<ApiResponse<null>>(`/master/employees/${id}`).then(unwrap),
}

export const usersApi = {
  list: (params?: { per_page?: number; page?: number }) =>
    apiClient.get<ApiResponse<Paginated<AccountUser>>>('/master/users', { params }).then(unwrap),
  get: (id: number) => apiClient.get<ApiResponse<AccountUser>>(`/master/users/${id}`).then(unwrap),
  create: (payload: { name: string; email: string; username: string; role: string; department_id?: number; employee_id?: number; is_active?: boolean; password: string; password_confirmation: string }) =>
    apiClient.post<ApiResponse<AccountUser>>('/master/users', payload).then(unwrap),
  update: (id: number, payload: { name: string; email: string; username: string; role: string; department_id?: number; employee_id?: number; is_active?: boolean; password?: string; password_confirmation?: string }) =>
    apiClient.put<ApiResponse<AccountUser>>(`/master/users/${id}`, payload).then(unwrap),
  remove: (id: number) => apiClient.delete<ApiResponse<null>>(`/master/users/${id}`).then(unwrap),
}

export interface AccessIndex {
  menus: MenuOption[]
  roles: RoleOption[]
}

export const accessApi = {
  list: () => apiClient.get<ApiResponse<AccessIndex>>('/access/permissions').then(unwrap),
  roleMatrix: (role: string) =>
    apiClient.get<ApiResponse<{ role: string; role_label: string; menus: MenuOption[]; matrix: Record<string, PermissionMatrix> }>>(`/access/permissions/roles/${role}`).then(unwrap),
  updateRole: (role: string, permissions: Record<string, PermissionMatrix>) =>
    apiClient.put<ApiResponse<Record<string, PermissionMatrix>>>(`/access/permissions/roles/${role}`, { permissions }).then(unwrap),
  userMatrix: (id: number) =>
    apiClient.get<ApiResponse<{ user: AccountUser; role: string; menus: MenuOption[]; overrides: Record<string, any>; effective: Record<string, PermissionMatrix> }>>(`/access/permissions/users/${id}`).then(unwrap),
  updateUser: (id: number, permissions: Record<string, any>) =>
    apiClient.put<ApiResponse<Record<string, any>>>(`/access/permissions/users/${id}`, { permissions }).then(unwrap),
}
