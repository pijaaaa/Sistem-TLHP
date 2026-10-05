import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { evidenceApi, type EvidenceUploadItem } from '@/api/evidence'
import { findingDistributionApi } from '@/api/findingDistribution'

export const useEvidenceList = (actionPlanId: number) =>
  useQuery({
    queryKey: ['evidence', actionPlanId],
    queryFn: () => evidenceApi.list(actionPlanId),
    enabled: !!actionPlanId,
  })

export const useSubmitEvidence = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ actionPlanId, files }: { actionPlanId: number; files: EvidenceUploadItem[] }) =>
      evidenceApi.submit(actionPlanId, files),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['evidence', variables.actionPlanId] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}

export const useApproveEvidence = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: evidenceApi.approve,
    onSuccess: (_data, actionPlanId) => {
      qc.invalidateQueries({ queryKey: ['evidence', actionPlanId] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
      qc.invalidateQueries({ queryKey: ['finding-departments'] })
    },
  })
}

export const useRequestEvidenceRevision = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ actionPlanId, note }: { actionPlanId: number; note: string }) =>
      evidenceApi.requestRevision(actionPlanId, note),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['evidence', variables.actionPlanId] })
      qc.invalidateQueries({ queryKey: ['action-plans'] })
    },
  })
}

export const useForwardToIA = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (findingDepartmentId: number) =>
      findingDistributionApi.forwardToIA(findingDepartmentId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['finding-departments'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}
