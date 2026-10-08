import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { externalStatusApi } from '@/api/findings'

export const useExternalStatusRecords = (findingId: number) =>
  useQuery({
    queryKey: ['external-status', findingId],
    queryFn: () => externalStatusApi.records(findingId),
    enabled: !!findingId,
  })

export const useRecordExternalStatus = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: externalStatusApi.record,
    onSuccess: (_r, vars) => {
      qc.invalidateQueries({ queryKey: ['external-status', vars.findingId] })
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}