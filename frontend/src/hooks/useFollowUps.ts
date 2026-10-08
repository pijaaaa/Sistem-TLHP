import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { followUpsApi } from '@/api/findings'
import type { FollowUp, FollowUpRowInput } from '@/types/finding'

export const useFollowUps = (params?: { page?: number; per_page?: number; status?: string; action_plan_id?: number | string; finding_id?: number | string }) =>
  useQuery({
    queryKey: ['follow-ups', params],
    queryFn: () => followUpsApi.list(params),
  })

export const useFollowUpsByActionPlan = (actionPlanId: number) =>
  useQuery({
    queryKey: ['follow-ups', { action_plan_id: actionPlanId, per_page: 100 }],
    queryFn: () => followUpsApi.list({ action_plan_id: actionPlanId, per_page: 100 }),
  })

export const useCreateFollowUps = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { actionPlanId: number; rows: FollowUpRowInput[] }) =>
      followUpsApi.create(payload.actionPlanId, payload.rows),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['follow-ups'] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}

export const useSubmitFollowUps = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: followUpsApi.submit,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['follow-ups'] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}

export const useUpdateFollowUp = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number } & Partial<FollowUpRowInput>) => followUpsApi.update(payload.id, payload),
    onSuccess: (fu: FollowUp) => {
      qc.invalidateQueries({ queryKey: ['follow-ups'] })
      qc.invalidateQueries({ queryKey: ['follow-ups', fu.id] })
    },
  })
}