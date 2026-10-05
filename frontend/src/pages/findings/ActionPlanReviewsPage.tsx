import { useState } from 'react'
import {
  useActionPlans,
  useApproveActionPlan,
  useRejectActionPlan,
  useRequestRevision,
  useOverrideWeight,
} from '@/hooks/useActionPlans'
import { DataTable, PageHeader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, StatusBadge, InputField, Textarea } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { ActionPlan } from '@/types/finding'
import { getActionPlanStatusLabel } from '@/types/finding'
import { isAxiosError } from 'axios'

type DialogState = { type: 'reject' | 'revision' | 'weight'; plan: ActionPlan } | null

export default function ActionPlanReviewsPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [dialog, setDialog] = useState<DialogState>(null)
  const [reason, setReason] = useState('')
  const [weight, setWeight] = useState('')
  const [busyId, setBusyId] = useState<number | null>(null)

  const { data, isLoading, refetch } = useActionPlans({ page, per_page: 15 })
  const approve = useApproveActionPlan()
  const reject = useRejectActionPlan()
  const revision = useRequestRevision()
  const overrideWeight = useOverrideWeight()

  const submitted = (data?.data ?? []).filter((r) => r.status === 'diajukan')

  const openDialog = (type: DialogState['type'], plan: ActionPlan) => {
    setDialog({ type, plan })
    setReason('')
    setWeight(plan.weight)
  }

  const handleApprove = async (ap: ActionPlan) => {
    setBusyId(ap.id)
    try {
      await approve.mutateAsync(ap.id)
      showToast('Rencana aksi berhasil disetujui', 'success')
      refetch()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyetujui' : 'Gagal menyetujui', 'error')
    } finally {
      setBusyId(null)
    }
  }

  const handleDialogSubmit = async () => {
    if (!dialog) return
    setBusyId(dialog.plan.id)
    try {
      if (dialog.type === 'reject') {
        await reject.mutateAsync({ id: dialog.plan.id, reason })
        showToast('Rencana aksi ditolak', 'success')
      } else if (dialog.type === 'revision') {
        await revision.mutateAsync({ id: dialog.plan.id, reason })
        showToast('Permintaan revisi dikirim', 'success')
      } else {
        await overrideWeight.mutateAsync({ id: dialog.plan.id, weight: Number(weight) })
        showToast('Bobot berhasil diubah', 'success')
      }
      setDialog(null)
      refetch()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    } finally {
      setBusyId(null)
    }
  }

  const columns: Column<ActionPlan>[] = [
    { key: 'title', header: 'Judul', body: (r) => r.title },
    { key: 'finding_department', header: 'Temuan', body: (r) => r.finding_department?.finding?.code ?? r.finding_department_id },
    { key: 'creator', header: 'PIC', body: (r) => r.creator?.name ?? r.created_by },
    { key: 'weight', header: 'Bobot (%)', body: (r) => r.weight },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getActionPlanStatusLabel(r.status)} />,
    },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) =>
        r.status === 'diajukan' ? (
          <div className="flex gap-2 flex-wrap">
            <Button
              size="sm"
              variant="outline"
              onClick={() => handleApprove(r)}
              disabled={busyId === r.id}
            >
              Setujui
            </Button>
            <Button size="sm" variant="outline" onClick={() => openDialog('revision', r)}>
              Revisi
            </Button>
            <Button size="sm" variant="outline" onClick={() => openDialog('weight', r)}>
              Ubah Bobot
            </Button>
            <Button size="sm" variant="destructive" onClick={() => openDialog('reject', r)}>
              Tolak
            </Button>
          </div>
        ) : (
          <Button size="sm" variant="outline" onClick={() => openDialog('weight', r)}>
            Ubah Bobot
          </Button>
        ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Review Rencana Aksi"
        subtitle="Tinjau dan putuskan rencana aksi yang diajukan PIC"
      />

      <DataTable
        data={submitted}
        columns={columns}
        loading={isLoading}
        emptyMessage="Tidak ada rencana aksi yang menunggu review"
        permissionMenu="action_plan_reviews"
        permissionAction="view"
      />

      <Modal
        open={!!dialog}
        onClose={() => setDialog(null)}
        title={
          dialog?.type === 'reject' ? 'Tolak Rencana Aksi'
          : dialog?.type === 'revision' ? 'Minta Revisi'
          : 'Ubah Bobot'
        }
      >
        <div className="space-y-3">
          {dialog?.type === 'weight' ? (
            <InputField
              label="Bobot (%)"
              type="number"
              value={weight}
              onChange={(e) => setWeight(e.target.value)}
              min={0}
              max={100}
              required
            />
          ) : (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Alasan</label>
              <Textarea
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                placeholder={
                  dialog?.type === 'reject'
                    ? 'Jelaskan alasan penolakan...'
                    : 'Jelaskan bagian yang perlu direvisi...'
                }
              />
            </div>
          )}
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setDialog(null)}>Batal</Button>
            <Button
              onClick={handleDialogSubmit}
              disabled={busyId === dialog?.plan.id}
              variant={dialog?.type === 'reject' ? 'destructive' : 'default'}
            >
              Simpan
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
