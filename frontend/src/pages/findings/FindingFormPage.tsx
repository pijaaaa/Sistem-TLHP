import { useNavigate, useParams } from 'react-router-dom'
import { FindingForm } from '@/components/shared/finding-form'
import { PageHeader } from '@/components/shared'
import { Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { useCreateFinding, useUpdateFinding, useFinding } from '@/hooks/useFindings'
import { usePermission } from '@/hooks/usePermission'
import type { FindingPayload } from '@/types/finding'
import { isAxiosError } from 'axios'

export default function FindingFormPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const { showToast } = useToast()
  const editingId = id ? Number(id) : null
  const { data: finding, isLoading } = useFinding(editingId ?? 0)
  const create = useCreateFinding()
  const update = useUpdateFinding()
  const perm = usePermission('findings')

  if (editingId && !perm.update) {
    return null
  }

  const submit = async (payload: FindingPayload) => {
    try {
      if (editingId) {
        await update.mutateAsync({ id: editingId, ...payload })
        showToast('Temuan berhasil diperbarui.', 'success')
        navigate(`/temuan/${editingId}`)
      } else {
        const created = await create.mutateAsync(payload)
        showToast('Temuan draft berhasil dibuat.', 'success')
        navigate(`/temuan/${created.id}`)
      }
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan temuan' : 'Gagal menyimpan temuan', 'error')
    }
  }

  return (
    <div className="max-w-3xl space-y-4">
      <PageHeader
        title={editingId ? 'Edit Temuan' : 'Tambah Temuan'}
        subtitle="Data LHP dari auditor eksternal"
      />

      {editingId && isLoading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
      ) : (
        <div className="bg-white rounded-lg shadow p-6">
          <FindingForm
            initial={finding ? {
              title: finding.title,
              source: finding.source ?? '',
              source_name: finding.source_name,
              lhp_number: finding.lhp_number,
              lhp_date: finding.lhp_date,
              finding_date: finding.finding_date,
              response_period_start: finding.response_period_start,
              response_period_end: finding.response_period_end,
              scope: finding.scope,
            } : undefined}
            submitLabel={editingId ? 'Simpan Perubahan' : 'Simpan Draft'}
            submitting={create.isPending || update.isPending}
            onSubmit={submit}
          />
        </div>
      )}
    </div>
  )
}