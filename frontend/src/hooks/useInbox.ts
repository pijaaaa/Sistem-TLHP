import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { inboxApi } from '@/api/findings'

export const useInbox = (params?: { page?: number; per_page?: number; status?: string; task_type?: string }) =>
  useQuery({
    queryKey: ['inbox', params],
    queryFn: () => inboxApi.list(params),
  })

export const useOpenInboxSubject = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { subject_type: string; subject_id: number }) => inboxApi.open(payload.subject_type, payload.subject_id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['inbox'] }),
  })
}