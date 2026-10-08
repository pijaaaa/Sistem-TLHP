import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { actionPlansApi } from '@/api/findings'
import type { ActionPlan, ActionPlanPayload } from '@/types/finding'

export const useActionPlans = (params?: { page?: number; per_page?: number; status?: string; finding_id?: number | string; department_id?: number | string }) =>
  useQuery({
    queryKey: ['action-plans', params],
    queryFn: () => actionPlansApi.list(params),
  })

export const useActionPlan = (id: number) =>
  useQuery({
    queryKey: ['action-plans', id],
    queryFn: () => actionPlansApi.get(id),
    enabled: !!id,
  })

export const useCreateActionPlan = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: actionPlansApi.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}

export const useUpdateActionPlan = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number } & Partial<ActionPlanPayload>) => actionPlansApi.update(payload.id, payload),
    onSuccess: (ap: ActionPlan) => {
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['action-plans', ap.id] })
    },
  })
}

export const useDeleteActionPlan = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: actionPlansApi.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['action-plans'] }),
  })
}

export const useSendActionPlans = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: actionPlansApi.send,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}

export const useChangeDeadline = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; deadline: string; reason?: string }) =>
      actionPlansApi.changeDeadline(payload.id, payload.deadline, payload.reason),
    onSuccess: (ap: ActionPlan) => {
      qc.invalidateQueries({ queryKey: ['action-plans', ap.id] })
    },
  })
}

export const useAssignPics = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; user_ids: number[] }) => actionPlansApi.assignPics(payload.id, payload.user_ids),
    onSuccess: (ap: ActionPlan) => {
      qc.invalidateQueries({ queryKey: ['action-plans', ap.id] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}

export const useActionPlanDocuments = (id: number) =>
  useQuery({
    queryKey: ['action-plan-documents', id],
    queryFn: () => actionPlansApi.documents(id),
    enabled: !!id,
  })

export const useUploadActionPlanDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; file: File; label: string }) =>
      actionPlansApi.uploadDocument(payload.id, payload.file, payload.label),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['action-plan-documents', variables.id] })
    },
  })
}

export const useDeleteActionPlanDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; documentId: number }) =>
      actionPlansApi.deleteDocument(payload.id, payload.documentId),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['action-plan-documents', variables.id] })
    },
  })
}