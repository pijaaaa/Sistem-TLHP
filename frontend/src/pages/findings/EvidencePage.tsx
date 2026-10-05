import { useState } from 'react'
import { useActionPlans } from '@/hooks/useActionPlans'
import {
  useEvidenceList,
  useSubmitEvidence,
  useApproveEvidence,
  useRequestEvidenceRevision,
} from '@/hooks/useEvidence'
import { DataTable, PageHeader, FileUploader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import type { FileWithLabel } from '@/components/shared/file-uploader'
import { Button, Modal, StatusBadge, Textarea } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { ActionPlan, EvidenceSubmission } from '@/types/finding'
import { getActionPlanStatusLabel, getEvidenceStatusLabel } from '@/types/finding'
import { isAxiosError } from 'axios'

export default function EvidencePage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [uploadPlan, setUploadPlan] = useState<ActionPlan | null>(null)
  const [files, setFiles] = useState<FileWithLabel[]>([])
  const [revisionPlan, setRevisionPlan] = useState<ActionPlan | null>(null)
  const [note, setNote] = useState('')
  const [busyId, setBusyId] = useState<number | null>(null)

  const { data, isLoading, refetch } = useActionPlans({ page, per_page: 15 })
  const submitEvidence = useSubmitEvidence()
  const approveEvidence = useApproveEvidence()
  const requestRevision = useRequestEvidenceRevision()

  const relevant = (data?.data ?? []).filter((r) =>
    ['menunggu_evidence', 'evidence_diajukan', 'evidence_disetujui', 'evidence_revisi'].includes(r.status),
  )

  const { data: evidenceData } = useEvidenceList(uploadPlan?.id ?? 0)
  const submissions = evidenceData ?? []

  const handleUpload = async () => {
    if (!uploadPlan || files.length === 0) return
    setBusyId(uploadPlan.id)
    try {
      await submitEvidence.mutateAsync({
        actionPlanId: uploadPlan.id,
        files: files.map((f) => ({ file: f.file, label: f.label })),
      })
      showToast('Evidence berhasil diajukan', 'success')
      setUploadPlan(null)
      setFiles([])
      refetch()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengajukan' : 'Gagal mengajukan', 'error')
    } finally {
      setBusyId(null)
    }
  }

  const handleApprove = async (ap: ActionPlan) => {
    setBusyId(ap.id)
    try {
      await approveEvidence.mutateAsync(ap.id)
      showToast('Evidence berhasil disetujui', 'success')
      refetch()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyetujui' : 'Gagal menyetujui', 'error')
    } finally {
      setBusyId(null)
    }
  }

  const handleRevision = async () => {
    if (!revisionPlan) return
    setBusyId(revisionPlan.id)
    try {
      await requestRevision.mutateAsync({ actionPlanId: revisionPlan.id, note })
      showToast('Permintaan revisi dikirim', 'success')
      setRevisionPlan(null)
      setNote('')
      refetch()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengirim' : 'Gagal mengirim', 'error')
    } finally {
      setBusyId(null)
    }
  }

  const columns: Column<ActionPlan>[] = [
    { key: 'title', header: 'Judul', body: (r) => r.title },
    { key: 'weight', header: 'Bobot (%)', body: (r) => r.weight },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getActionPlanStatusLabel(r.status)} />,
    },
    {
      key: 'evidence',
      header: 'Evidence',
      body: (r) =>
        r.latest_evidence ? (
          <span className="text-sm">{getEvidenceStatusLabel(r.latest_evidence.status)}</span>
        ) : (
          <span className="text-gray-400">-</span>
        ),
    },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2 flex-wrap">
          {(r.status === 'menunggu_evidence' || r.status === 'evidence_revisi') && (
            <Button
              size="sm"
              variant="outline"
              onClick={() => { setUploadPlan(r); setFiles([]) }}
            >
              Unggah Evidence
            </Button>
          )}
          {r.status === 'evidence_diajukan' && (
            <>
              <Button
                size="sm"
                variant="outline"
                onClick={() => handleApprove(r)}
                disabled={busyId === r.id}
              >
                Setujui
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={() => { setRevisionPlan(r); setNote('') }}
              >
                Revisi
              </Button>
            </>
          )}
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader title="Evidence" subtitle="Kelola evidence tindak lanjut" />

      <DataTable
        data={relevant}
        columns={columns}
        loading={isLoading}
        emptyMessage="Tidak ada evidence yang perlu diproses"
        permissionMenu="evidence"
        permissionAction="view"
      />

      <Modal
        open={!!uploadPlan}
        onClose={() => setUploadPlan(null)}
        title={`Unggah Evidence: ${uploadPlan?.title ?? ''}`}
      >
        <div className="space-y-3">
          {submissions.length > 0 && (
            <div className="space-y-2">
              <h4 className="text-sm font-medium">Riwayat Pengajuan</h4>
              {submissions.map((s: EvidenceSubmission) => (
                <div key={s.id} className="p-2 border rounded text-sm">
                  <div className="flex items-center justify-between">
                    <StatusBadge status={getEvidenceStatusLabel(s.status)} />
                    <span className="text-xs text-gray-500">{s.created_at}</span>
                  </div>
                  {s.revision_note && (
                    <p className="mt-1 text-xs text-red-600">Catatan: {s.revision_note}</p>
                  )}
                  <ul className="mt-1 space-y-0.5">
                    {s.files?.map((f) => (
                      <li key={f.id}>
                        <a
                          href={f.download_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-blue-600 hover:underline text-xs"
                        >
                          {f.name}
                        </a>
                        {f.label && <span className="text-xs text-gray-500 ml-1">({f.label})</span>}
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          )}

          <div>
            <h4 className="text-sm font-medium mb-2">File Evidence Baru</h4>
            <FileUploader
              onFilesChange={(f) => setFiles(f)}
              maxFiles={10}
              maxSizeMB={10}
              allowedTypes={['pdf', 'docx', 'doc', 'xlsx', 'xls', 'jpg', 'jpeg', 'png', 'csv']}
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => { setUploadPlan(null); setFiles([]) }}>Batal</Button>
            <Button onClick={handleUpload} disabled={files.length === 0 || busyId === uploadPlan?.id}>
              Ajukan
            </Button>
          </div>
        </div>
      </Modal>

      <Modal
        open={!!revisionPlan}
        onClose={() => setRevisionPlan(null)}
        title={`Minta Revisi: ${revisionPlan?.title ?? ''}`}
      >
        <div className="space-y-3">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Catatan Revisi</label>
            <Textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Jelaskan bagian evidence yang perlu diperbaiki..."
            />
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setRevisionPlan(null)}>Batal</Button>
            <Button onClick={handleRevision} disabled={busyId === revisionPlan?.id}>Kirim</Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
