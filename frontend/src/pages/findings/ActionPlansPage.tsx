import { useState } from 'react'
import {
  useActionPlans,
  useCreateActionPlan,
  useUpdateActionPlan,
  useDeleteActionPlan,
  useSubmitActionPlan,
  useActionPlanDocuments,
  useUploadActionPlanDocument,
  useDeleteActionPlanDocument,
} from '@/hooks/useActionPlans'
import { useFindingDepartments } from '@/hooks/useFindingDistribution'
import { actionPlansApi } from '@/api/actionPlans'
import { DataTable, PageHeader, Can, FileUploader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, ConfirmDialog, StatusBadge, InputField, Textarea } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { ActionPlan, ActionPlanForm, ActionPlanDocument } from '@/types/finding'
import type { FileWithLabel } from '@/components/shared/file-uploader'
import { getActionPlanStatusLabel, getActionPlanStatusVariant } from '@/types/finding'
import { isAxiosError } from 'axios'

const initialForm: ActionPlanForm = {
  title: '',
  description: '',
  weight: '',
  due_date: '',
}

export default function ActionPlansPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<ActionPlan | null>(null)
  const [docPlan, setDocPlan] = useState<ActionPlan | null>(null)
  const [uploaderFiles, setUploaderFiles] = useState<FileWithLabel[]>([])
  const [deleteItem, setDeleteItem] = useState<ActionPlan | null>(null)
  const [submittingId, setSubmittingId] = useState<number | null>(null)

  const { data, isLoading, refetch } = useActionPlans({ page, per_page: 15 })
  const { data: fdData } = useFindingDepartments({ page: 1, per_page: 100 })
  const create = useCreateActionPlan()
  const update = useUpdateActionPlan()
  const remove = useDeleteActionPlan()
  const submit = useSubmitActionPlan()
  const uploadDoc = useUploadActionPlanDocument()
  const deleteDoc = useDeleteActionPlanDocument()

  const findingDepartments = fdData?.data ?? []

  const openAdd = (fd: any) => {
    setEditing(null)
    setForm({ ...initialForm })
    setSelectedFd(fd)
    setModalOpen(true)
  }
  const openEdit = (row: ActionPlan) => {
    setEditing(row)
    setForm({
      title: row.title,
      description: row.description ?? '',
      weight: row.weight,
      due_date: row.due_date ?? '',
    })
    setSelectedFd(row.finding_department)
    setModalOpen(true)
  }

  const [selectedFd, setSelectedFd] = useState<any>(null)
  const [form, setForm] = useState<ActionPlanForm>({ ...initialForm })

  const handleSubmit = async () => {
    if (!selectedFd) return
    try {
      if (editing) {
        await update.mutateAsync({ id: editing.id, ...form })
      } else {
        await create.mutateAsync({ findingDepartmentId: selectedFd.id, ...form })
      }
      showToast('Rencana aksi berhasil disimpan', 'success')
      setModalOpen(false)
      refetch()
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan',
        'error',
      )
    }
  }

  const handleDelete = async () => {
    if (!deleteItem) return
    await remove.mutateAsync(deleteItem.id)
    showToast('Rencana aksi berhasil dihapus', 'success')
    setDeleteItem(null)
    refetch()
  }

  const handleSubmitActionPlan = async (ap: ActionPlan) => {
    setSubmittingId(ap.id)
    try {
      await submit.mutateAsync(ap.id)
      showToast('Rencana aksi berhasil diajukan', 'success')
      refetch()
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengajukan' : 'Gagal mengajukan',
        'error',
      )
    } finally {
      setSubmittingId(null)
    }
  }

  const openDocuments = (ap: ActionPlan) => {
    setDocPlan(ap)
    setUploaderFiles([])
  }

  const { data: docData } = useActionPlanDocuments(docPlan?.id ?? 0)
  const documents = docData ?? []

  const handleUploadDocs = async () => {
    if (!docPlan || uploaderFiles.length === 0) return
    try {
      for (const f of uploaderFiles) {
        await uploadDoc.mutateAsync({
          actionPlanId: docPlan.id,
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

  const handleDeleteDoc = async (doc: ActionPlanDocument) => {
    try {
      await deleteDoc.mutateAsync({ actionPlanId: docPlan!.id, documentId: doc.id })
      showToast('Dokumen berhasil dihapus', 'success')
    } catch {
      showToast('Gagal menghapus dokumen', 'error')
    }
  }

  const canCreate = (row: ActionPlan) => row.status === 'draft' || row.status === 'revisi'
  const canSubmit = (row: ActionPlan) => row.status === 'draft' || row.status === 'revisi'
  const canUploadDocs = (row: ActionPlan) => ['draft', 'diajukan', 'revisi'].includes(row.status)

  const columns: Column<ActionPlan>[] = [
    { key: 'title', header: 'Judul', body: (r) => r.title },
    { key: 'finding_department', header: 'Temuan', body: (r) => r.finding_department?.finding?.code ?? r.finding_department_id },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getActionPlanStatusLabel(r.status)} />,
    },
    { key: 'weight', header: 'Bobot (%)', body: (r) => r.weight },
    { key: 'due_date', header: 'Jatuh Tempo', body: (r) => r.due_date ?? '-' },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2 flex-wrap">
          <Can menu="action_plans" action="update">
            {canSubmit(r) && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => handleSubmitActionPlan(r)}
                disabled={submittingId === r.id}
              >
                {submittingId === r.id ? 'Mengajukan...' : 'Ajukan'}
              </Button>
            )}
          </Can>
          <Can menu="action_plans" action="update">
            {canUploadDocs(r) && (
              <Button size="sm" variant="outline" onClick={() => openDocuments(r)}>
                Dokumen
              </Button>
            )}
          </Can>
          <Can menu="action_plans" action="update">
            <Button size="sm" variant="outline" onClick={() => openEdit(r)}>Edit</Button>
          </Can>
          <Can menu="action_plans" action="delete">
            <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Rencana Aksi"
        subtitle="Kelola tindak lanjut temuan"
        permissionMenu="action_plans"
        permissionAction="create"
        action={<Button onClick={() => { setEditing(null); setForm({ ...initialForm }); setSelectedFd(null); setModalOpen(true) }}>+ Tambah</Button>}
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
        permissionMenu="action_plans"
        permissionAction="view"
        emptyMessage="Tidak ada rencana aksi"
      />

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Edit Rencana Aksi' : 'Tambah Rencana Aksi'}>
        <div className="space-y-3 max-h-[70vh] overflow-y-auto">
          {editing ? null : (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
              <select
                value={selectedFd?.id ?? ''}
                onChange={(e) => {
                  const fd = findingDepartments.find((d: any) => d.id === Number(e.target.value))
                  setSelectedFd(fd ?? null)
                }}
                className="w-full px-3 py-2 border border-gray-300 rounded"
              >
                <option value="">Pilih departemen...</option>
                {findingDepartments.map((fd: any) => (
                  <option key={fd.id} value={fd.id}>{fd.department?.name ?? fd.department_id} - {fd.finding?.code}</option>
                ))}
              </select>
            </div>
          )}
          <InputField
            label="Judul"
            value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })}
            required
          />
          <Textarea
            label="Deskripsi"
            value={form.description}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
            placeholder="Deskripsi tindak lanjut..."
          />
          <InputField
            label="Bobot (%)"
            type="number"
            value={form.weight}
            onChange={(e) => setForm({ ...form, weight: e.target.value })}
            placeholder="0-100"
            min={0}
            max={100}
          />
          <InputField
            label="Jatuh Tempo"
            type="date"
            value={form.due_date}
            onChange={(e) => setForm({ ...form, due_date: e.target.value })}
          />
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setModalOpen(false)}>Batal</Button>
            <Button onClick={handleSubmit}>Simpan</Button>
          </div>
        </div>
      </Modal>

      {docPlan && (
        <Modal
          open={!!docPlan}
          onClose={() => setDocPlan(null)}
          title={`Dokumen: ${docPlan.title}`}
        >
          <div className="space-y-4">
            {documents.length > 0 && (
              <div className="space-y-2">
                <h4 className="text-sm font-medium">Dokumen Ada</h4>
                {documents.map((doc: ActionPlanDocument) => (
                  <div key={doc.id} className="flex items-center justify-between p-2 border rounded text-sm">
                    <div>
                      <span className="font-medium">{doc.name}</span>
                      <span className="text-gray-500 ml-2">({doc.label ?? 'Tanpa label'})</span>
                    </div>
                    <div className="flex gap-2">
                      <a
                        href={doc.download_url || actionPlansApi.downloadDocument(doc.id)}
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
              <Button variant="outline" onClick={() => { setDocPlan(null); setUploaderFiles([]) }}>Batal</Button>
              <Button onClick={handleUploadDocs} disabled={uploaderFiles.length === 0}>Unggah</Button>
            </div>
          </div>
        </Modal>
      )}

      <ConfirmDialog
        open={!!deleteItem}
        title="Hapus Rencana Aksi?"
        message={`Yakin hapus rencana aksi "${deleteItem?.title}"?`}
        variant="danger"
        onConfirm={handleDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}
