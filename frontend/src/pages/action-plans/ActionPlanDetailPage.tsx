import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useActionPlan, useSendActionPlans, useAssignPics, useChangeDeadline, useDeleteActionPlan, useActionPlanDocuments, useUploadActionPlanDocument, useDeleteActionPlanDocument } from '@/hooks/useActionPlans'
import { useStaffByDepartment } from '@/hooks/useLookups'
import { PageHeader, Can, DocumentPanel } from '@/components/shared'
import { Button, Spinner, StatusBadge, Modal, ConfirmDialog, InputField } from '@/components/ui'
import { EmployeeMultiSelect } from '@/components/shared/employee-multi-select'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import { useAuth } from '@/contexts/AuthContext'
import { actionPlansApi } from '@/api/findings'
import { getActionPlanStatusVariant, RISK_OPTIONS } from '@/types/finding'
import { isAxiosError } from 'axios'

const riskVariant = (risk?: string | null) =>
  RISK_OPTIONS.find((r) => r.value === risk)?.variant ?? 'default'

export default function ActionPlanDetailPage() {
  const { id } = useParams<{ id: string }>()
  const apId = Number(id)
  const navigate = useNavigate()
  const { showToast } = useToast()
  const { user } = useAuth()
  const perm = usePermission('action_plans')

  const { data: ap, isLoading } = useActionPlan(apId)
  const sendAps = useSendActionPlans()
  const assignPics = useAssignPics()
  const deadline = useChangeDeadline()
  const removeAp = useDeleteActionPlan()

  const { data: docs, isLoading: docsLoading } = useActionPlanDocuments(apId)
  const uploadDoc = useUploadActionPlanDocument()
  const deleteDoc = useDeleteActionPlanDocument()

  const [pics, setPics] = useState<number[]>([])
  const [assignOpen, setAssignOpen] = useState(false)
  const [deadlineOpen, setDeadlineOpen] = useState(false)
  const [deadlineValue, setDeadlineValue] = useState('')
  const [deadlineReason, setDeadlineReason] = useState('')
  const [deleteOpen, setDeleteOpen] = useState(false)

  const { data: staff } = useStaffByDepartment(ap?.department_id ?? 0)

  const isManagerOfDept =
    ap && user?.role === 'manager_dept' && user.department?.id === ap.department_id
  const canAssign =
    isManagerOfDept && ap && ap.status !== 'CLOSED' && ap.status !== 'DRAFT'

  const doSend = async () => {
    try {
      await sendAps.mutateAsync([apId])
      showToast('Action plan dikirim.', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengirim' : 'Gagal mengirim', 'error')
    }
  }

  const doAssign = async () => {
    if (pics.length === 0) {
      showToast('Pilih minimal satu PIC.', 'error')
      return
    }
    try {
      await assignPics.mutateAsync({ id: apId, user_ids: pics })
      showToast('PIC berhasil ditentukan.', 'success')
      setPics([])
      setAssignOpen(false)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menunjuk PIC' : 'Gagal menunjuk PIC', 'error')
    }
  }

  const doDeadline = async () => {
    if (!deadlineValue) return
    try {
      await deadline.mutateAsync({ id: apId, deadline: deadlineValue, reason: deadlineReason || undefined })
      showToast('Deadline diperbarui.', 'success')
      setDeadlineOpen(false)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal memperbarui deadline' : 'Gagal memperbarui deadline', 'error')
    }
  }

  const doDelete = async () => {
    try {
      await removeAp.mutateAsync(apId)
      showToast('Action plan dihapus.', 'success')
      navigate('/action-plan')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menghapus' : 'Gagal menghapus', 'error')
    }
  }

  if (isLoading) {
    return <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
  }

  if (!ap) return null

  return (
    <div className="space-y-4 max-w-4xl">
      <PageHeader
        title={ap.code}
        subtitle={ap.title}
        action={
          <div className="flex gap-2 flex-wrap">
            <Can menu="action_plans" action="create">
              {ap.status === 'DRAFT' && <Button onClick={doSend}>Kirim</Button>}
            </Can>
            <Can menu="action_plans" action="update">
              {ap.status !== 'DRAFT' && ap.status !== 'CLOSED' && (
                <Button variant="outline" onClick={() => { setDeadlineValue(ap.deadline ?? ''); setDeadlineOpen(true) }}>Ubah Deadline</Button>
              )}
            </Can>
            <Can menu="action_plans" action="delete">
              {ap.status === 'DRAFT' && <Button variant="destructive" onClick={() => setDeleteOpen(true)}>Hapus</Button>}
            </Can>
          </div>
        }
      />

      <div className="flex gap-2 items-center flex-wrap">
        <StatusBadge status={ap.status_label} variant={getActionPlanStatusVariant(ap.status)} />
        <StatusBadge status={ap.risk_label ?? '-'} variant={riskVariant(ap.risk)} />
        <span className="text-sm text-gray-500">Departemen: {ap.department?.name ?? '-'}</span>
        <span className="text-sm text-gray-500">Deadline: {ap.deadline ?? '-'}</span>
        <span className="text-sm text-gray-500">Progress: {ap.progress ?? 0}%</span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2 bg-white rounded-lg shadow p-6 space-y-3">
          <Row label="Kondisi (condition)" value={ap.condition ?? '-'} />
          <Row label="Kriteria (criteria)" value={ap.criteria ?? '-'} />
          <Row label="Sebab (cause)" value={ap.cause ?? '-'} />
          <Row label="Dampak (impact)" value={ap.impact ?? '-'} />
          <Row label="Potensi Kerugian" value={`${ap.loss_idr ? 'Rp ' + Number(ap.loss_idr).toLocaleString('id-ID') : '-'}${ap.loss_usd ? ' · USD ' + ap.loss_usd : ''}`} />
          <Row label="Revisi" value={`${ap.current_revision}`} />
          <Row label="Dikirim" value={ap.sent_at ? new Date(ap.sent_at).toLocaleString('id-ID') : '-'} />
        </div>

        <div className="space-y-4">
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-semibold mb-3">PIC</h3>
            {ap.assignees && ap.assignees.length > 0 ? (
              <ul className="space-y-1 text-sm">
                {ap.assignees.map((u) => (
                  <li key={u.id}>{u.name} <span className="text-gray-400">@{u.username}</span></li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-gray-500">{ap.status === 'DRAFT' ? 'Belum dikirim.' : 'Belum ada PIC.'}</p>
            )}

            <div className="mt-4">
              <Can menu="action_plans" action="update">
                {canAssign && (
                  <>
                    {ap.status === 'MENUNGGU_PENENTUAN_PIC' && (
                      <p className="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-2 mb-3">
                        Tentukan minimal satu PIC (staff departemen) untuk mulai proses tindak lanjut.
                      </p>
                    )}
                    <Button size="sm" onClick={() => setAssignOpen(true)}>Tentukan / Ubah PIC</Button>
                  </>
                )}
              </Can>
              {!canAssign && isManagerOfDept && ap.status === 'DRAFT' && (
                <p className="text-xs text-gray-500">Action plan belum dikirim; PIC ditentukan setelah dikirim.</p>
              )}
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-semibold mb-3">Dokumen</h3>
            <DocumentPanel
              documents={docs}
              loading={docsLoading}
              canManage={!!perm.update && ap.status !== 'CLOSED'}
              download={(docId) => actionPlansApi.downloadDocument(apId, docId)}
              onUpload={async (file, label) => uploadDoc.mutateAsync({ id: apId, file, label })}
              onDelete={async (docId) => deleteDoc.mutateAsync({ id: apId, documentId: docId })}
            />
          </div>
        </div>
      </div>

      <Modal open={assignOpen} onClose={() => setAssignOpen(false)} title="Tentukan PIC">
        <div className="space-y-4">
          <EmployeeMultiSelect
            label="PIC (staff departemen) *"
            value={pics}
            onChange={setPics}
            staff={staff ?? []}
          />
          <p className="text-xs text-gray-500">
            Minimal 1 PIC. Hanya manager departemen pemilik action plan yang dapat menunjuk.
          </p>
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setAssignOpen(false)}>Batal</Button>
            <Button onClick={doAssign} disabled={assignPics.isPending}>Simpan</Button>
          </div>
        </div>
      </Modal>

      <Modal open={deadlineOpen} onClose={() => setDeadlineOpen(false)} title={`Ubah Deadline — ${ap.code}`}>
        <div className="space-y-4">
          <InputField label="Deadline Baru *" type="date" value={deadlineValue} onChange={(e) => setDeadlineValue(e.target.value)} />
          <InputField label="Alasan (opsional)" value={deadlineReason} onChange={(e) => setDeadlineReason(e.target.value)} />
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setDeadlineOpen(false)}>Batal</Button>
            <Button onClick={doDeadline} disabled={deadline.isPending}>Simpan</Button>
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={deleteOpen}
        title="Hapus Action Plan?"
        message={`Yakin hapus action plan ${ap.code}?`}
        variant="danger"
        onConfirm={doDelete}
        onClose={() => setDeleteOpen(false)}
      />
    </div>
  )
}

const Row = ({ label, value }: { label: string; value: string }) => (
  <div className="grid grid-cols-1 md:grid-cols-4 gap-1">
    <span className="text-sm text-gray-500">{label}</span>
    <span className="text-sm text-gray-900 md:col-span-3 whitespace-pre-wrap">{value}</span>
  </div>
)