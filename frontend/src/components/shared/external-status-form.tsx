import { useState } from 'react'
import { FormField, Input, Textarea, Button } from '@/components/ui'
import { FileUploader, type FileWithLabel } from '@/components/shared/file-uploader'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { useToast } from '@/components/ui/toast'
import { useActionPlans } from '@/hooks/useActionPlans'
import { useRecordExternalStatus } from '@/hooks/useExternalStatus'
import { EXTERNAL_STATUS_OPTIONS, type ExternalStatus } from '@/types/finding'
import { isAxiosError } from 'axios'

interface ExternalStatusFormProps {
  findingId: number
  findingLabel: string
  onDone: () => void
}

const ExternalStatusForm = ({ findingId, findingLabel, onDone }: ExternalStatusFormProps) => {
  const { showToast } = useToast()
  const [status, setStatus] = useState<ExternalStatus>('SSR')
  const [note, setNote] = useState('')
  const [newDeadline, setNewDeadline] = useState('')
  const [files, setFiles] = useState<FileWithLabel[]>([])
  const [apIds, setApIds] = useState<number[]>([])
  const [confirmSsr, setConfirmSsr] = useState(false)

  const selected = EXTERNAL_STATUS_OPTIONS.find((o) => o.value === status)!
  const { data: aps } = useActionPlans({ finding_id: findingId, status: 'SESUAI', per_page: 100 })
  const record = useRecordExternalStatus()

  const submit = async () => {
    if (files.length === 0) {
      showToast('Minimal satu dokumen pendukung wajib diunggah.', 'error')
      return
    }
    if (!selected.closes && apIds.length === 0) {
      showToast('Pilih minimal satu action plan yang akan diperbaiki.', 'error')
      return
    }
    try {
      await record.mutateAsync({
        findingId,
        status,
        note: note.trim() || null,
        new_deadline: newDeadline || null,
        files: files.map((f) => ({ file: f.file, label: f.label || 'Dokumen' })),
        action_plan_ids: selected.closes ? undefined : apIds,
      })
      showToast('Status eksternal berhasil dicatat.', 'success')
      onDone()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    }
  }

  return (
    <div className="space-y-4">
      <p className="text-sm text-gray-500">{findingLabel}</p>

      <FormField label="Status Auditor Eksternal *">
        <div className="space-y-2">
          {EXTERNAL_STATUS_OPTIONS.map((o) => (
            <label key={o.value} className="flex items-center gap-2 text-sm">
              <input
                type="radio"
                checked={status === o.value}
                onChange={() => {
                  setStatus(o.value)
                  setApIds([])
                }}
                className="h-4 w-4 text-blue-600"
              />
              {o.label}
            </label>
          ))}
        </div>
      </FormField>

      {selected.closes && (
        <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded p-3">
          SSR menutup temuan dan seluruh action plan secara otomatis. Tindakan tidak dapat dibatalkan.
        </p>
      )}

      {!selected.closes && (
        <>
          <FormField label="Action Plan yang Diperbaiki *">
            {(aps?.data ?? []).length === 0 ? (
              <p className="text-sm text-gray-500">Tidak ada action plan berstatus Sesuai.</p>
            ) : (
              <div className="border border-gray-300 rounded max-h-48 overflow-y-auto p-3 space-y-1">
                {(aps?.data ?? []).map((ap) => (
                  <label key={ap.id} className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={apIds.includes(ap.id)}
                      onChange={() =>
                        setApIds((prev) => (prev.includes(ap.id) ? prev.filter((x) => x !== ap.id) : [...prev, ap.id]))
                      }
                      className="h-4 w-4 rounded text-blue-600"
                    />
                    <span>{ap.code} — {ap.title}</span>
                  </label>
                ))}
              </div>
            )}
          </FormField>
          <FormField label="Deadline Baru (opsional)">
            <Input type="date" value={newDeadline} onChange={(e) => setNewDeadline(e.target.value)} className="max-w-xs" />
          </FormField>
        </>
      )}

      <FormField label="Catatan">
        <Textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} />
      </FormField>

      <FormField label="Dokumen Pendukung (wajib, berlabel)">
        <FileUploader maxFiles={5} onFilesChange={setFiles} labelPlaceholder="Label dokumen..." />
      </FormField>

      <div className="flex justify-end">
        <Button onClick={() => (selected.closes ? setConfirmSsr(true) : submit())} disabled={record.isPending}>
          {record.isPending ? 'Menyimpan...' : selected.closes ? 'Catat & Tutup Temuan (SSR)' : 'Simpan Status'}
        </Button>
      </div>

      <ConfirmDialog
        open={confirmSsr}
        title="Menutup Temuan?"
        message={`Status SSR akan menutup temuan "${findingLabel}" dan seluruh action plan-nya. Yakin lanjut?`}
        variant="danger"
        onConfirm={submit}
        onClose={() => setConfirmSsr(false)}
      />
    </div>
  )
}

export { ExternalStatusForm }