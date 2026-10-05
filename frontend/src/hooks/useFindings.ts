import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { findingsApi } from '@/api/findings'
import type { Finding, FindingDocument, FindingPayload, FindingDocumentPayload } from '@/types/finding'

export const useFindings = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['findings', params],
    queryFn: () => findingsApi.list(params),
  })

export const useFinding = (id: number) =>
  useQuery({
    queryKey: ['findings', id],
    queryFn: () => findingsApi.get(id),
    enabled: !!id,
  })

export const useCreateFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: FindingPayload) => findingsApi.create(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}

export const useUpdateFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number } & FindingPayload) =>
      findingsApi.update(payload.id, payload),
    onSuccess: (finding: Finding) => {
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['findings', finding.id] })
    },
  })
}

export const useDeleteFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: findingsApi.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['findings'] }),
  })
}

export const useSendToIA = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: findingsApi.sendToIA,
    onSuccess: (finding: Finding) => {
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['findings', finding.id] })
    },
  })
}

export const usePendingAssessments = (params?: { page?: number; per_page?: number }) =>
  useQuery({
    queryKey: ['pending-assessments', params],
    queryFn: () => findingsApi.pendingAssessment(params),
  })

export const useAssessFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({
      id,
      ...payload
    }: {
      id: number
      assessment_status: string
      note?: string | null
      department_ids?: number[]
    }) => findingsApi.assess(id, payload),
    onSuccess: (finding: Finding) => {
      qc.invalidateQueries({ queryKey: ['pending-assessments'] })
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['findings', finding.id] })
      qc.invalidateQueries({ queryKey: ['finding-departments'] })
    },
  })
}

export const useFindingDocuments = (findingId: number) =>
  useQuery({
    queryKey: ['finding-documents', findingId],
    queryFn: () => findingsApi.documents(findingId),
    enabled: !!findingId,
  })

export const useUploadFindingDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { findingId: number } & FindingDocumentPayload) =>
      findingsApi.uploadDocument(payload.findingId, payload),
    onSuccess: (_data: FindingDocument, variables: { findingId: number }) => {
      qc.invalidateQueries({ queryKey: ['finding-documents', variables.findingId] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}

export const useDeleteFindingDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { findingId: number; documentId: number }) =>
      findingsApi.deleteDocument(payload.findingId, payload.documentId),
    onSuccess: (_data: unknown, variables: { findingId: number }) => {
      qc.invalidateQueries({ queryKey: ['finding-documents', variables.findingId] })
      qc.invalidateQueries({ queryKey: ['findings'] })
    },
  })
}
