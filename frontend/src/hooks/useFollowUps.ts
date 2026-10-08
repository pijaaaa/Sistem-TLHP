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

const invalidateFu = (qc: ReturnType<typeof useQueryClient>, fu: FollowUp) => {
  qc.invalidateQueries({ queryKey: ['follow-ups'] })
  qc.invalidateQueries({ queryKey: ['follow-ups', fu.id] })
}

export const useApproveFollowUp = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: followUpsApi.approve,
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}

export const useRequestFollowUpRevision = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; note: string }) => followUpsApi.requestRevision(payload.id, payload.note),
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}

export const useRejectFollowUp = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; note: string }) => followUpsApi.reject(payload.id, payload.note),
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}

export const useReturnFollowUpToRevision = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; note: string }) => followUpsApi.returnToRevision(payload.id, payload.note),
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}

export const useOverrideFollowUpWeight = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; weight: number }) => followUpsApi.overrideWeight(payload.id, payload.weight),
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}

export const useFollowUpReviews = (followUpId: number) =>
  useQuery({
    queryKey: ['follow-up-reviews', followUpId],
    queryFn: () => followUpsApi.reviews(followUpId),
    enabled: !!followUpId,
  })

export const useFollowUpComments = (followUpId: number) =>
  useQuery({
    queryKey: ['follow-up-comments', followUpId],
    queryFn: () => followUpsApi.comments(followUpId),
    enabled: !!followUpId,
  })

export const useAddFollowUpComment = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; kind: string; body: string }) => followUpsApi.addComment(payload.id, payload.kind, payload.body),
    onSuccess: (_c, vars) => {
      qc.invalidateQueries({ queryKey: ['follow-up-comments', vars.id] })
    },
  })
}

export const useFollowUpProgressReports = (followUpId: number) =>
  useQuery({
    queryKey: ['follow-up-progress', followUpId],
    queryFn: () => followUpsApi.progressReports(followUpId),
    enabled: !!followUpId,
  })

export const useReportFollowUpProgress = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; value: number; note: string | null; files: { file: File; label: string }[] }) =>
      followUpsApi.reportProgress(payload.id, payload.value, payload.note, payload.files),
    onSuccess: (_r, vars) => {
      qc.invalidateQueries({ queryKey: ['follow-up-progress', vars.id] })
      qc.invalidateQueries({ queryKey: ['follow-ups'] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}

export const useApproveCompletion = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: followUpsApi.approveCompletion,
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}

export const useCompletionRevision = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; note: string }) => followUpsApi.completionRevision(payload.id, payload.note),
    onSuccess: (fu) => invalidateFu(qc, fu),
  })
}