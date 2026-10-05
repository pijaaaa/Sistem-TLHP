import axios from 'axios'
import type { AuthMe } from '@/types/auth'

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  withCredentials: true,
})

let logoutCallback: (() => void) | null = null

export const setAuthLogout = (cb: () => void) => {
  logoutCallback = cb
}

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      logoutCallback?.()
    }
    return Promise.reject(error)
  }
)

export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string | null
}

export const fetchAuthMe = async (): Promise<AuthMe> => {
  const { data } = await apiClient.get<ApiResponse<AuthMe>>('/auth/me')
  return data.data
}
