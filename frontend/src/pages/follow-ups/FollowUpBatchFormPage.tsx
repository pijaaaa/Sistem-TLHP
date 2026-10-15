import { useNavigate, useParams } from 'react-router-dom'
import { FollowUpBatchForm, type SubmitMode } from '@/components/shared/follow-up-batch-form'
import { PageHeader, Can } from '@/components/shared'
import { Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { useActionPlan } from '@/hooks/useActionPlans'
import { useCreateFollowUps, useSubmitFollowUps, useFollowUpsByActionPlan } from '@/hooks/useFollowUps'
import { isAxiosError } from 'axios'
import { motion } from 'framer-motion'
import { useMemo } from 'react'

export default function FollowUpBatchFormPage() {
  const { id } = useParams<{ id: string }>()
  const apId = Number(id)
  const navigate = useNavigate()
  const { showToast } = useToast()

  const { data: ap, isLoading } = useActionPlan(apId)
  const create = useCreateFollowUps()
  const submit = useSubmitFollowUps()

  const { data: allFups } = useFollowUpsByActionPlan(apId)
  const currentRevision = ap?.current_revision ?? 0
  const linkCandidates = currentRevision > 0
    ? (allFups?.data ?? [])
        .filter((f) => f.revision_no < currentRevision)
        .map((f) => ({ id: f.id, label: `TL #${f.id} · ${f.description.slice(0, 60)}` }))
    : undefined
  
  // Calculate total weight of existing follow-ups in the CURRENT revision (not being created in this form)
  const existingWeight = useMemo(() => {
    return (allFups?.data ?? [])
      .filter((f) => f.revision_no === currentRevision && f.status !== 'DITOLAK')
      .reduce((sum, f) => sum + (Number(f.weight) || 0), 0)
  }, [allFups, currentRevision])

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
    <div className="max-w-5xl mx-auto space-y-6">
      <PageHeader 
        title="Susun Tindak Lanjut" 
        subtitle={ap ? `${ap.code} — ${ap.title}` : 'Menyusun rencana kegiatan PIC'} 
      />

      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Spinner /> Memuat action plan...
          </div>
        </div>
      ) : !ap ? null : (
        <Can menu="follow_ups" action="create">
          {ap.status !== 'PROSES_TINDAK_LANJUT' ? (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              className="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
            >
              <p className="text-sm text-yellow-800">
                <strong>Perhatian:</strong> Tindak lanjut hanya dapat disusun saat action plan berstatus <strong>Proses Tindak Lanjut</strong>.
              </p>
            </motion.div>
          ) : (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.3 }}
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 md:p-8"
            >
              <FollowUpBatchForm
                actionPlan={ap}
                linkCandidates={linkCandidates}
                submitting={create.isPending || submit.isPending}
                onSubmit={handleSubmit}
                existingWeight={existingWeight}
              />
            </motion.div>
          )}
        </Can>
      )}
    </div>
  )
}