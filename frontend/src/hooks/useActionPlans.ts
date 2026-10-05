import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { actionPlansApi } from '@/api/actionPlans'
import type { ActionPlan, ActionPlanForm, ActionPlanDocument } from '@/types/finding'

export const useActionPlans = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['action-plans', params],
    queryFn: () => actionPlansApi.list(params),
    staleTime: 30_000,
  })

export const useActionPlan = (id: number) =>
  useQuery({
    queryKey: ['action-plans', id],
    queryFn: () => actionPlansApi.get(id),
    enabled: !!id,
    staleTime: 10_000,
  })

export const useCreateActionPlan = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ findingDepartmentId, ...payload }: { findingDepartmentId: number } & ActionPlanForm) =>
      actionPlansApi.create(findingDepartmentId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}

export const useUpdateActionPlan = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...payload }: { id: number } & ActionPlanForm) =>
      actionPlansApi.update(id, payload),
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

export const useSubmitActionPlan = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: actionPlansApi.submit,
    onSuccess: (ap: ActionPlan) => {
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['action-plans', ap.id] })
    },
  })
}

export const useActionPlanDocuments = (actionPlanId: number) =>
  useQuery({
    queryKey: ['action-plan-documents', actionPlanId],
    queryFn: () => actionPlansApi.documents(actionPlanId),
    enabled: !!actionPlanId,
  })

export const useUploadActionPlanDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { actionPlanId: number } & { document: File; label?: string | null }) =>
      actionPlansApi.uploadDocument(payload.actionPlanId, {
        document: payload.document,
        label: payload.label,
      }),
    onSuccess: (_: ActionPlanDocument, variables: { actionPlanId: number }) => {
      qc.invalidateQueries({ queryKey: ['action-plan-documents', variables.actionPlanId] })
    },
  })
}

export const useDeleteActionPlanDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ actionPlanId, documentId }: { actionPlanId: number; documentId: number }) =>
      actionPlansApi.deleteDocument(actionPlanId, documentId),
    onSuccess: (_: unknown, variables: { actionPlanId: number }) => {
      qc.invalidateQueries({ queryKey: ['action-plan-documents', variables.actionPlanId] })
    },
  })
}
