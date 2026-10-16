import { useState } from 'react'
import { Modal, Button, Input, Textarea, FormField, Spinner } from '@/components/ui'
import { ProgressBar } from '@/components/shared/progress-bar'
import { ReviewTimeline } from '@/components/shared/review-timeline'
import { CommentThread } from '@/components/shared/comment-thread'
import { FollowUpStatusBadge } from '@/components/shared/follow-up-status-badge'
import { FileUploader, type FileWithLabel } from '@/components/shared/file-uploader'
import { useToast } from '@/components/ui/toast'
import { useAuth } from '@/contexts/AuthContext'
import { usePermission } from '@/hooks/usePermission'
import { confirmDownload } from '@/lib/utils'
import {
  useFollowUpProgressReports,
  useReportFollowUpProgress,
  useUpdateFollowUp,
} from '@/hooks/useFollowUps'
import { followUpsApi } from '@/api/findings'
import type { FollowUp } from '@/types/finding'
import { isAxiosError } from 'axios'

interface FollowUpDetailModalProps {
  followUp: FollowUp | null
  onClose: () => void
}

const FollowUpDetailModal = ({ followUp, onClose }: FollowUpDetailModalProps) => {
  const { user } = useAuth()
  const { showToast } = useToast()
  const { data: reports, isLoading } = useFollowUpProgressReports(followUp?.id ?? 0)
  const report = useReportFollowUpProgress()
  const update = useUpdateFollowUp()

  const [value, setValue] = useState('')
  const [note, setNote] = useState('')
  const [files, setFiles] = useState<FileWithLabel[]>([])

  const perm = usePermission('follow_ups')
  const editableStatus = followUp?.status === 'DRAFT' || followUp?.status === 'REVISI'
  const [editing, setEditing] = useState(false)
  const [editForm, setEditForm] = useState({
    description: followUp?.description ?? '',
    target_date: followUp?.target_date ?? '',
    weight: followUp?.weight ?? '',
  })

  const openEdit = () => {
    setEditForm({
      description: followUp?.description ?? '',
      target_date: followUp?.target_date ?? '',
      weight: followUp?.weight ?? '',
    })
    setEditing(true)
  }

  const submitEdit = async () => {
    if (!editForm.description.trim()) {
      showToast('Deskripsi wajib diisi.', 'error')
      return
    }
    try {
      await update.mutateAsync({
        id: followUp.id,
        description: editForm.description,
        target_date: editForm.target_date,
        weight: Number(editForm.weight) || undefined,
      })
      showToast('Tindak lanjut diperbarui.', 'success')
      setEditing(false)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal memperbarui.' : 'Gagal memperbarui.', 'error')
    }
  }

  if (!followUp) return null

  const current = Number(followUp.progress) || 0
  const minProgress = Math.min(current + 10, 100)
  const canReport = followUp.status === 'DISETUJUI'

  const submit = async () => {
    const v = Number(value)
    if (!value || Number.isNaN(v) || v < minProgress) {
      showToast(`Nilai progres minimal ${minProgress}% (progres terkini: ${current}%).`, 'error')
      return
    }
    try {
      await report.mutateAsync({
        id: followUp.id,
        value: v,
        note: note.trim() || null,
        files: files.map((f) => ({ file: f.file, label: f.label || 'Dokumen' })),
      })
      showToast('Progres berhasil dilaporkan.', 'success')
      setValue('')
      setNote('')
      setFiles([])
      onClose()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal melaporkan progres.' : 'Gagal melaporkan progres.', 'error')
    }
  }

  return (
    <Modal open onClose={onClose} title={`Tindak Lanjut #${followUp.id}`} className="max-w-2xl">
      <div className="space-y-4">
        <div className="flex items-start justify-between gap-3 flex-wrap">
          <div className="min-w-0">
            <p className="text-sm font-medium text-gray-800">{followUp.description}</p>
            <p className="text-xs text-gray-500">
              Target {followUp.target_date} · Bobot {followUp.weight} · Revisi {followUp.revision_no}
            </p>
          </div>
          <FollowUpStatusBadge status={followUp.status} label={followUp.status_label} />
        </div>

        <ProgressBar value={followUp.progress} />

        {perm.update && editableStatus && (
          <div className="bg-gray-50 border border-gray-200 rounded p-4 space-y-3">
            {!editing ? (
              <div className="flex justify-between items-center">
                <h4 className="text-sm font-semibold">Revisi Tindak Lanjut</h4>
                <Button size="sm" onClick={openEdit}>Edit</Button>
              </div>
            ) : (
              <div className="space-y-3">
                <FormField label="Deskripsi *">
                  <Textarea
                    rows={2}
                    value={editForm.description}
                    onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
                  />
                </FormField>
                <FormField label="Target Tanggal">
                  <Input
                    type="date"
                    value={editForm.target_date}
                    onChange={(e) => setEditForm({ ...editForm, target_date: e.target.value })}
                  />
                </FormField>
                <FormField label="Bobot">
                  <Input
                    type="number"
                    min={1}
                    max={100}
                    value={editForm.weight}
                    onChange={(e) => setEditForm({ ...editForm, weight: e.target.value.replace(/\D/g, '') })}
                  />
                </FormField>
                <div className="flex justify-end gap-2">
                  <Button size="sm" variant="outline" onClick={() => setEditing(false)}>Batal</Button>
                  <Button size="sm" onClick={submitEdit} disabled={update.isPending}>
                    {update.isPending ? 'Menyimpan...' : 'Simpan'}
                  </Button>
                </div>
              </div>
            )}
          </div>
        )}

        <div>
          <h4 className="text-sm font-semibold mb-2">Riwayat Laporan Progres</h4>
          {isLoading ? (
            <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
          ) : !reports || reports.length === 0 ? (
            <p className="text-sm text-gray-500">Belum ada laporan progres.</p>
          ) : (
            <ul className="divide-y divide-gray-100">
              {reports.map((r) => (
                <li key={r.id} className="py-2">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <span className="text-sm font-medium text-gray-800">{r.progress_value}%</span>
                      <span className="text-xs text-gray-400 ml-2">{r.reported_at ? new Date(r.reported_at).toLocaleString('id-ID') : ''}</span>
                    </div>
                    {r.documents.map((d) => (
                      <Button key={d.id} size="sm" variant="outline" onClick={() => confirmDownload(followUpsApi.progressReportDownload(followUp.id, r.id, d.id))}>
                        {d.label}
                      </Button>
                    ))}
                  </div>
                  {r.note && <p className="text-sm text-gray-600 mt-1">{r.note}</p>}
                </li>
              ))}
            </ul>
          )}
        </div>

        <div>
          <h4 className="text-sm font-semibold mb-2">Catatan Manager (Keputusan)</h4>
          <ReviewTimeline followUpId={followUp.id} />
        </div>

        {(user?.role === 'staff_dept' || user?.role === 'super_admin') && canReport && (
          <div className="bg-gray-50 border border-gray-200 rounded p-4 space-y-3">
            <h4 className="text-sm font-semibold">Lapor Progres</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <FormField label={`Progres % (min ${minProgress}%)`}>
                <Input
                  type="number"
                  min={minProgress}
                  max={100}
                  step={1}
                  value={value}
                  onChange={(e) => setValue(e.target.value)}
                />
              </FormField>
              <FormField label="Catatan">
                <Textarea rows={2} value={note} onChange={(e) => setNote(e.target.value)} />
              </FormField>
            </div>
            <FormField label="Dokumen Pendukung (berlabel)">
              <FileUploader maxFiles={5} onFilesChange={setFiles} labelPlaceholder="Label dokumen..." />
            </FormField>
            <div className="flex justify-end">
              <Button onClick={submit} disabled={report.isPending || !value}>
                {report.isPending ? 'Menyimpan...' : 'Lapor Progres'}
              </Button>
            </div>
          </div>
        )}

        <div className="lg:max-w-xl">
          <CommentThread followUpId={followUp.id} commentKind="DISKUSI" canComment placeholder="Diskusi tindak lanjut..." />
        </div>
      </div>
    </Modal>
  )
}

export { FollowUpDetailModal }