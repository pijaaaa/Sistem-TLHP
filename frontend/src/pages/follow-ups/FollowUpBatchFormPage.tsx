import { useNavigate, useParams } from 'react-router-dom'
import { FollowUpBatchForm, type SubmitMode } from '@/components/shared/follow-up-batch-form'
import { PageHeader, Can } from '@/components/shared'
import { Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { useActionPlan } from '@/hooks/useActionPlans'
import { useCreateFollowUps, useSubmitFollowUps } from '@/hooks/useFollowUps'
import { isAxiosError } from 'axios'

export default function FollowUpBatchFormPage() {
  const { id } = useParams<{ id: string }>()
  const apId = Number(id)
  const navigate = useNavigate()
  const { showToast } = useToast()

  const { data: ap, isLoading } = useActionPlan(apId)
  const create = useCreateFollowUps()
  const submit = useSubmitFollowUps()

  const handleSubmit = async (rows: Parameters<typeof create.mutateAsync>[0]['rows'], mode: SubmitMode) => {
    if (!ap) return
    try {
      const created = await create.mutateAsync({ actionPlanId: ap.id, rows })

      if (mode === 'submit') {
        await submit.mutateAsync(created.map((fu) => fu.id))
        showToast(`Tindak lanjut diajukan ke manager (${created.length} baris).`, 'success')
      } else {
        showToast(`Tindak lanjut disimpan sebagai draft (${created.length} baris).`, 'success')
      }

      navigate(`/action-plan/${ap.id}#tindak-lanjut`)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan tindak lanjut.' : 'Gagal menyimpan tindak lanjut.', 'error')
    }
  }

  return (
    <div className="max-w-4xl space-y-4">
      <PageHeader title="Susun Tindak Lanjut" subtitle={ap ? `${ap.code} — ${ap.title}` : 'Menyusun rencana kegiatan PIC'} />

      {isLoading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
      ) : !ap ? null : (
        <Can menu="follow_ups" action="create">
          {ap.status !== 'PROSES_TINDAK_LANJUT' ? (
            <p className="text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-3">
              Tindak lanjut hanya dapat disusun saat action plan berstatus Proses Tindak Lanjut.
            </p>
          ) : (
            <div className="bg-white rounded-lg shadow p-6">
              <FollowUpBatchForm
                actionPlan={ap}
                submitting={create.isPending || submit.isPending}
                onSubmit={handleSubmit}
              />
            </div>
          )}
        </Can>
      )}
    </div>
  )
}