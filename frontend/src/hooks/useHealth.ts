import { useQuery } from '@tanstack/react-query'
import { apiClient } from '@/api/client'

export const useHealth = () => {
  return useQuery({
    queryKey: ['health'],
    queryFn: () => apiClient.get('/health').then((res) => res.data),
    staleTime: 60000,
    retry: 3,
  })
}
