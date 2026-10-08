import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useActionPlan, useSendActionPlans, useAssignPics, useChangeDeadline, useDeleteActionPlan, useActionPlanDocuments, useUploadActionPlanDocument, useDeleteActionPlanDocument, useSubmitActionPlanToSpi } from '@/hooks/useActionPlans'
import { useSpiBundle, useForwardToPic } from '@/hooks/useSpiReview'
import { useFollowUpsByActionPlan, useSubmitFollowUps } from '@/hooks/useFollowUps'
import { useStaffByDepartment } from '@/hooks/useLookups'
import { PageHeader, Can, DocumentPanel, Tabs, FollowUpStatusBadge, ProgressBar, FollowUpDetailModal } from '@/components/shared'
import { Button, Spinner, StatusBadge, Modal, ConfirmDialog, InputField } from '@/components/ui'
import { EmployeeMultiSelect } from '@/components/shared/employee-multi-select'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import { useAuth } from '@/contexts/AuthContext'
import { actionPlansApi } from '@/api/findings'
import { getActionPlanStatusVariant, RISK_OPTIONS, type FollowUp } from '@/types/finding'
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

  const { data: followUps } = useFollowUpsByActionPlan(apId)
  const submitFu = useSubmitFollowUps()
  const submitToSpi = useSubmitActionPlanToSpi()
  const forward = useForwardToPic()
  const { data: spiBundle } = useSpiBundle(ap?.status === 'REVISI_SPI' ? apId : 0)
  const [detailFu, setDetailFu] = useState<FollowUp | null>(null)

  const [tab, setTab] = useState<'ringkasan' | 'tindak-lanjut' | 'dokumen'>('ringkasan')
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

  const fups = followUps?.data ?? []
  const activeFups = fups.filter((f) => f.status !== 'DITOLAK')
  const allSelesai = activeFups.length > 0 && activeFups.every((f) => f.status === 'SELESAI')
  const totalWeight = activeFups.reduce((s, f) => s + f.weight, 0)
  const weightFits = activeFups.length > 0 && totalWeight === 100
  const canSubmitToSpi = isManagerOfDept && ap?.status === 'PROSES_TINDAK_LANJUT' && allSelesai && weightFits

  const submitApToSpi = async () => {
    try {
      await submitToSpi.mutateAsync(apId)
      showToast('Action plan diajukan ke Admin SPI.', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengajukan' : 'Gagal mengajukan', 'error')
    }
  }

  const doForward = async () => {
    try {
      await forward.mutateAsync(apId)
      showToast('Revisi diteruskan ke PIC.', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal meneruskan' : 'Gagal meneruskan', 'error')
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

      {isManagerOfDept && ap.status === 'REVISI_SPI' && (
        <div className="bg-yellow-50 border border-yellow-200 rounded p-4 space-y-2">
          <p className="text-sm font-medium">Revisi SPI menunggu diteruskan ke PIC</p>
          {spiBundle?.revisions && spiBundle.revisions.length > 0 && (
            <div className="text-sm text-gray-700">
              <p>{spiBundle.revisions[spiBundle.revisions.length - 1].reason}</p>
              {spiBundle.revisions[spiBundle.revisions.length - 1].new_deadline && (
                <p className="text-xs text-gray-600">Deadline baru: {spiBundle.revisions[spiBundle.revisions.length - 1].new_deadline}</p>
              )}
            </div>
          )}
          <Button size="sm" onClick={doForward} disabled={forward.isPending}>Teruskan ke PIC</Button>
        </div>
      )}

      <Tabs
        tabs={[
          { key: 'ringkasan', label: 'Ringkasan' },
          { key: 'tindak-lanjut', label: 'Tindak Lanjut', badge: followUps?.data.length ?? 0 },
          { key: 'dokumen', label: 'Dokumen', badge: docs?.length ?? 0 },
        ]}
        active={tab}
        onChange={(k) => setTab(k as typeof tab)}
      />

      {tab === 'ringkasan' && (
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
        </div>
      </div>
      )}

      {tab === 'dokumen' && (
        <div className="bg-white rounded-lg shadow p-6">
          <DocumentPanel
            documents={docs}
            loading={docsLoading}
            canManage={!!perm.update && ap.status !== 'CLOSED'}
            download={(docId) => actionPlansApi.downloadDocument(apId, docId)}
            onUpload={async (file, label) => uploadDoc.mutateAsync({ id: apId, file, label })}
            onDelete={async (docId) => deleteDoc.mutateAsync({ id: apId, documentId: docId })}
          />
        </div>
      )}

      {tab === 'tindak-lanjut' && (
        <div className="bg-white rounded-lg shadow p-6 space-y-3">
          <div className="flex items-center justify-between gap-3 flex-wrap">
            <div className="flex gap-2">
              <Can menu="follow_ups" action="create">
                {ap.status === 'PROSES_TINDAK_LANJUT' && (
                  <Link
                    to={`/action-plan/${apId}/tindak-lanjut/susun`}
                    className="inline-flex items-center justify-center h-10 px-4 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                  >
                    + Susun Tindak Lanjut
                  </Link>
                )}
              </Can>
              <Can menu="action_plans" action="update">
                {isManagerOfDept && ap.status === 'PROSES_TINDAK_LANJUT' && (
                  <div>
                    <Button onClick={submitApToSpi} disabled={!canSubmitToSpi || submitToSpi.isPending}>Ajukan ke Admin SPI</Button>
                    {(!allSelesai || !weightFits) && (
                      <p className="text-xs text-gray-500 mt-2 whitespace-pre-line">
                        {activeFups.length === 0
                          ? '· Belum ada tindak lanjut.\n'
                          : (allSelesai ? '' : '· Semua tindak lanjut harus Selesai.\n') + (weightFits ? '' : `· Total bobot aktif harus tepat 100 (sekarang ${totalWeight}).`)}
                      </p>
                    )}
                  </div>
                )}
              </Can>
            </div>
          </div>

          {(followUps?.data ?? []).length === 0 ? (
            <p className="text-sm text-gray-500">Belum ada tindak lanjut untuk action plan ini.</p>
          ) : (
            <table className="w-full">
              <thead>
                <tr className="bg-gray-50">
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Uraian</th>
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Target</th>
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Bobot</th>
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Progres</th>
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Status</th>
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">PIC</th>
                  <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {(followUps?.data ?? []).map((fu) => (
                  <tr key={fu.id} className="border-t">
                    <td className="px-4 py-2 text-sm">{fu.description}</td>
                    <td className="px-4 py-2 text-sm whitespace-nowrap">{fu.target_date}</td>
                    <td className="px-4 py-2 text-sm">{fu.weight}</td>
                    <td className="px-4 py-2 w-36"><ProgressBar value={fu.progress} /></td>
                    <td className="px-4 py-2"><FollowUpStatusBadge status={fu.status} label={fu.status_label} /></td>
                    <td className="px-4 py-2 text-sm">{(fu.assignees ?? []).map((u) => u.name).join(', ') || '-'}</td>
                    <td className="px-4 py-2">
                      <div className="flex gap-2">
                        <Button size="sm" variant="outline" onClick={() => setDetailFu(fu)}>Detail / Progres</Button>
                        {(fu.status === 'DRAFT' || fu.status === 'REVISI') && (
                          <Button size="sm" variant="secondary" onClick={() => submitFu.mutateAsync([fu.id])}>Ajukan</Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

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

      <FollowUpDetailModal followUp={detailFu} onClose={() => setDetailFu(null)} />
    </div>
  )
}

const Row = ({ label, value }: { label: string; value: string }) => (
  <div className="grid grid-cols-1 md:grid-cols-4 gap-1">
    <span className="text-sm text-gray-500">{label}</span>
    <span className="text-sm text-gray-900 md:col-span-3 whitespace-pre-wrap">{value}</span>
  </div>
)