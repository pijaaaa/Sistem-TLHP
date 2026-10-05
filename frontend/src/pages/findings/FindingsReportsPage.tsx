import { useState } from 'react'
import { useFindings, useCreateFinding, useUpdateFinding, useDeleteFinding, useSendToIA, useUploadFindingDocument, useDeleteFindingDocument, useFindingDocuments } from '@/hooks/useFindings'
import { DataTable, PageHeader, Can, FileUploader } from '@/components/shared'
import { Button, Modal, ConfirmDialog, StatusBadge, InputField, Textarea, Select } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { Column } from '@/components/shared/data-table'
import type { FileWithLabel } from '@/components/shared/file-uploader'
import type { Finding, FindingForm, FindingDocument } from '@/types/finding'
import { FINDING_SEVERITY_OPTIONS, getFindingStatusLabel } from '@/types/finding'
import { isAxiosError } from 'axios'

const initialForm: FindingForm = {
  code: '',
  title: '',
  finding_date: '',
  severity: 'medium',
  recommendation: '',
  auditor_action_plan: '',
  is_active: true,
}

export default function FindingsReportsPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<Finding | null>(null)
  const [form, setForm] = useState<FindingForm>({ ...initialForm })
  const [deleteItem, setDeleteItem] = useState<Finding | null>(null)
  const [docFinding, setDocFinding] = useState<Finding | null>(null)
  const [uploaderFiles, setUploaderFiles] = useState<FileWithLabel[]>([])

  const { data, isLoading } = useFindings({ page, per_page: 15 })
  const create = useCreateFinding()
  const update = useUpdateFinding()
  const remove = useDeleteFinding()
  const sendToIA = useSendToIA()
  const uploadDoc = useUploadFindingDocument()
  const deleteDoc = useDeleteFindingDocument()

  const openAdd = () => {
    setEditing(null)
    setForm({ ...initialForm })
    setModalOpen(true)
  }
  const openEdit = (row: Finding) => {
    setEditing(row)
    setForm({
      code: row.code,
      title: row.title,
      finding_date: row.finding_date ?? '',
      severity: row.severity ?? 'medium',
      recommendation: row.recommendation ?? '',
      auditor_action_plan: row.auditor_action_plan ?? '',
      is_active: row.is_active,
    })
    setModalOpen(true)
  }
  const openDocuments = (finding: Finding) => {
    setDocFinding(finding)
    setUploaderFiles([])
  }

  const canSendToIA = (finding: Finding) => finding.status === 'draft'
  const canManageDocs = (finding: Finding) => ['draft', 'dikirim_ke_ia'].includes(finding.status)

  const submit = async () => {
    try {
      const payload = {
        ...form,
        finding_date: form.finding_date || null,
        severity: form.severity || null,
        recommendation: form.recommendation || null,
        auditor_action_plan: form.auditor_action_plan || null,
      }
      if (editing) {
        await update.mutateAsync({ id: editing.id, ...payload })
      } else {
        await create.mutateAsync(payload)
      }
      showToast('Temuan berhasil disimpan', 'success')
      setModalOpen(false)
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan',
        'error',
      )
    }
  }

  const submitDelete = async () => {
    if (!deleteItem) return
    await remove.mutateAsync(deleteItem.id)
    showToast('Temuan berhasil dihapus', 'success')
    setDeleteItem(null)
  }

  const handleSendToIA = async (finding: Finding) => {
    try {
      await sendToIA.mutateAsync(finding.id)
      showToast('Temuan berhasil dikirim ke IA', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengirim' : 'Gagal mengirim', 'error')
    }
  }

  const { data: docData } = useFindingDocuments(docFinding?.id ?? 0)
  const documents = docData ?? []

  const handleUploadDocs = async () => {
    if (!docFinding || uploaderFiles.length === 0) return
    try {
      for (const f of uploaderFiles) {
        await uploadDoc.mutateAsync({
          findingId: docFinding.id,
          document: f.file,
          label: f.label,
        })
      }
      showToast('Dokumen berhasil diunggah', 'success')
      setUploaderFiles([])
    } catch {
      showToast('Gagal mengunggah dokumen', 'error')
    }
  }

  const handleDeleteDoc = async (doc: FindingDocument) => {
    try {
      await deleteDoc.mutateAsync({ findingId: doc.finding_id, documentId: doc.id })
      showToast('Dokumen berhasil dihapus', 'success')
    } catch {
      showToast('Gagal menghapus dokumen', 'error')
    }
  }

  const columns: Column<Finding>[] = [
    { key: 'code', header: 'Kode', body: (r) => r.code },
    { key: 'title', header: 'Judul', body: (r) => r.title },
    {
      key: 'severity',
      header: 'Tingkat',
      body: (r) => <StatusBadge status={r.severity ?? 'medium'} />,
    },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getFindingStatusLabel(r.status)} />,
    },
    { key: 'documents_count', header: 'Dokumen', body: (r) => r.documents_count ?? 0 },
    { key: 'finding_date', header: 'Tgl Temuan', body: (r) => r.finding_date ?? '-' },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2 flex-wrap">
          <Can menu="findings.reports" action="update">
            {canSendToIA(r) && (
              <Button size="sm" variant="outline" onClick={() => handleSendToIA(r)}>
                Kirim ke IA
              </Button>
            )}
          </Can>
          <Can menu="findings.reports" action="update">
            {canManageDocs(r) && (
              <Button size="sm" variant="outline" onClick={() => openDocuments(r)}>
                Dokumen
              </Button>
            )}
          </Can>
          <Can menu="findings.reports" action="update">
            <Button size="sm" variant="outline" onClick={() => openEdit(r)}>Edit</Button>
          </Can>
          <Can menu="findings.reports" action="delete">
            <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Laporan Temuan"
        subtitle="Kelola temuan audit eksternal"
        permissionMenu="findings.reports"
        permissionAction="create"
        action={<Button onClick={openAdd}>+ Tambah Temuan</Button>}
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        loading={isLoading}
        pagination={data?.meta ? {
          current: data.meta.current_page,
          perPage: data.meta.per_page,
          total: data.meta.total,
          onChange: setPage,
        } : undefined}
        permissionMenu="findings.reports"
        permissionAction="view"
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? 'Edit Temuan' : 'Tambah Temuan'}
      >
        <div className="space-y-3 max-h-[70vh] overflow-y-auto">
          <InputField
            label="Kode"
            value={form.code}
            onChange={(e) => setForm({ ...form, code: e.target.value })}
            required
          />
          <InputField
            label="Judul"
            value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })}
            required
          />
          <InputField
            label="Tanggal Temuan"
            type="date"
            value={form.finding_date}
            onChange={(e) => setForm({ ...form, finding_date: e.target.value })}
          />
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Tingkat / Ketegasan</label>
            <Select
              value={form.severity}
              onChange={(e) => setForm({ ...form, severity: e.target.value })}
            >
              <option value="">Pilih...</option>
              {FINDING_SEVERITY_OPTIONS.map((o) => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </Select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Rekomendasi</label>
            <Textarea
              value={form.recommendation}
              onChange={(e) => setForm({ ...form, recommendation: e.target.value })}
              placeholder="Masukkan rekomendasi perbaikan..."
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Rencana Aksi Auditor</label>
            <Textarea
              value={form.auditor_action_plan}
              onChange={(e) => setForm({ ...form, auditor_action_plan: e.target.value })}
              placeholder="Masukkan rencana aksi auditor..."
            />
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setModalOpen(false)}>Batal</Button>
            <Button onClick={submit}>Simpan</Button>
          </div>
        </div>
      </Modal>

      {docFinding && (
        <Modal
          open={!!docFinding}
          onClose={() => setDocFinding(null)}
          title={`Dokumen: ${docFinding.title}`}
        >
          <div className="space-y-4 ">
            {documents.length > 0 && (
              <div className="space-y-2">
                <h4 className="text-sm font-medium">Dokumen Ada</h4>
                {documents.map((doc) => (
                  <div key={doc.id} className="flex items-center justify-between p-2 border rounded text-sm">
                    <div>
                      <span className="font-medium">{doc.name}</span>
                      <span className="text-gray-500 ml-2">({doc.label ?? 'Tanpa label'})</span>
                    </div>
                    <div className="flex gap-2">
                      <a
                        href={doc.download_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-600 hover:text-blue-700"
                      >
                        Unduh
                      </a>
                      <Button
                        size="sm"
                        variant="destructive"
                        onClick={() => handleDeleteDoc(doc)}
                      >
                        Hapus
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            )}
            <div>
              <h4 className="text-sm font-medium mb-2">Unggah Dokumen Baru</h4>
              <FileUploader
                onFilesChange={(files) => setUploaderFiles(files)}
                maxFiles={10}
                maxSizeMB={10}
                allowedTypes={['pdf', 'docx', 'doc', 'xlsx', 'xls', 'jpg', 'jpeg', 'png', 'csv']}
              />
            </div>
            <div className="flex justify-end gap-2 pt-2">
              <Button variant="outline" onClick={() => { setDocFinding(null); setUploaderFiles([]) }}>Batal</Button>
              <Button onClick={handleUploadDocs} disabled={uploaderFiles.length === 0}>Unggah</Button>
            </div>
          </div>
        </Modal>
      )}

      <ConfirmDialog
        open={!!deleteItem}
        title="Hapus Temuan?"
        message={`Yakin hapus temuan ${deleteItem?.code}?`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}
