import { useQuery } from '@tanstack/react-query'
import { lookupsApi } from '@/api/findings'

export const useAuditeeDepartments = () =>
  useQuery({
    queryKey: ['lookups', 'auditee-departments'],
    queryFn: lookupsApi.auditeeDepartments,
    select: (res) => res.data,
  })

export const useStaffByDepartment = (departmentId: number) =>
  useQuery({
    queryKey: ['lookups', 'staff', departmentId],
    queryFn: () => lookupsApi.staffByDepartment(departmentId),
    enabled: !!departmentId,
    select: (res) => res.data,
  })