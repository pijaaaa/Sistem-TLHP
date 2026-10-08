import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { findingsApi } from '@/api/findings'
import type { Finding, FindingPayload } from '@/types/finding'

export const useFindings = (params?: { page?: number; per_page?: number; status?: string; fiscal_year?: number | string; source?: string; department_id?: number | string; q?: string }) =>
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
    mutationFn: findingsApi.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['findings'] }),
  })
}

export const useUpdateFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number } & FindingPayload) => findingsApi.update(payload.id, payload),
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

export const useRegisterFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { id: number; department_ids: number[] }) => findingsApi.register(payload.id, payload.department_ids),
    onSuccess: (finding: Finding) => {
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['findings', finding.id] })
    },
  })
}

export const useActivateFinding = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: findingsApi.activate,
    onSuccess: (finding: Finding) => {
      qc.invalidateQueries({ queryKey: ['findings'] })
      qc.invalidateQueries({ queryKey: ['findings', finding.id] })
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
    mutationFn: (payload: { findingId: number; file: File; label: string }) =>
      findingsApi.uploadDocument(payload.findingId, payload.file, payload.label),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['finding-documents', variables.findingId] })
      qc.invalidateQueries({ queryKey: ['findings', variables.findingId] })
    },
  })
}

export const useDeleteFindingDocument = () => {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (payload: { findingId: number; documentId: number }) =>
      findingsApi.deleteDocument(payload.findingId, payload.documentId),
    onSuccess: (_data, variables) => {
      qc.invalidateQueries({ queryKey: ['finding-documents', variables.findingId] })
      qc.invalidateQueries({ queryKey: ['findings', variables.findingId] })
    },
  })
}