import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { spiApi, actionPlansApi, type SpiAssessItem } from '@/api/findings'

export const useSpiQueue = () =>
  useQuery({
    queryKey: ['spi-reviews', 'queue'],
    queryFn: spiApi.queue,
  })

export const useSpiBundle = (actionPlanId: number) =>
  useQuery({
    queryKey: ['spi-reviews', actionPlanId],
    queryFn: () => spiApi.bundle(actionPlanId),
    enabled: !!actionPlanId,
  })

export const useSpiAssess = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; items: SpiAssessItem[] }) => spiApi.assess(payload.id, payload.items),
    onSuccess: (_r, vars) => qc.invalidateQueries({ queryKey: ['spi-reviews', vars.id] }),
  })
}

export const useSpiComplete = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; newDeadline?: string | null }) => spiApi.complete(payload.id, payload.newDeadline),
    onSuccess: (_ap, vars) => {
      qc.invalidateQueries({ queryKey: ['spi-reviews'] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['spi-reviews', vars.id] })
    },
  })
}

export const useForwardToPic = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: actionPlansApi.forwardToPic,
    onSuccess: (ap) => {
      qc.invalidateQueries({ queryKey: ['action-plans', ap.id] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}