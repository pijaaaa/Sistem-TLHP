import { useState, useMemo } from 'react'
import { useMasterFindingSources, useCreateFindingSource, useUpdateFindingSource, useDeleteFindingSource } from '@/hooks/useFindingSources'
import { DataTable, PageHeader, Can } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, ConfirmDialog, Checkbox, StatusBadge, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { FindingSource } from '@/types/master'
import { isAxiosError } from 'axios'

export default function FindingSourcesPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<FindingSource | null>(null)
  const [form, setForm] = useState({ code: '', name: '', is_active: true })
  const [deleteItem, setDeleteItem] = useState<FindingSource | null>(null)

  const params = useMemo(() => ({ page, per_page: 15 }), [page])
  const { data, isLoading } = useMasterFindingSources(params)
  const create = useCreateFindingSource()
  const update = useUpdateFindingSource()
  const remove = useDeleteFindingSource()

  const openAdd = () => {
    setEditing(null)
    setForm({ code: '', name: '', is_active: true })
    setModalOpen(true)
  }
  const openEdit = (row: FindingSource) => {
    setEditing(row)
    setForm({ code: row.code, name: row.name, is_active: row.is_active })
    setModalOpen(true)
  }

  const submit = async () => {
    try {
      if (editing) {
        await update.mutateAsync({ id: editing.id, ...form })
      } else {
        await create.mutateAsync(form)
      }
      showToast('Berhasil menyimpan sumber temuan', 'success')
      setModalOpen(false)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    }
  }

  const submitDelete = async () => {
    if (!deleteItem) return
    await remove.mutateAsync(deleteItem.id)
    setDeleteItem(null)
  }

  const columns: Column<FindingSource>[] = [
    { key: 'code', header: 'Kode', body: (r) => r.code },
    { key: 'name', header: 'Nama', body: (r) => r.name },
    { key: 'is_active', header: 'Status', body: (r) => <StatusBadge status={r.is_active ? 'Aktif' : 'Tidak Aktif'} /> },
    {
      key: 'actions', header: 'Aksi', body: (r) => (
        <div className="flex gap-2">
          <Can menu="master.finding_sources" action="update">
            <Button size="sm" variant="outline" onClick={() => openEdit(r)}>Edit</Button>
          </Can>
          <Can menu="master.finding_sources" action="delete">
            <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Sumber Temuan"
        subtitle="Daftar sumber auditor eksternal (BPK, BPKP, KAP, dll.) yang muncul di form temuan"
        permissionMenu="master.finding_sources"
        permissionAction="create"
        action={<Button onClick={openAdd}>+ Tambah Sumber</Button>}
      />
      <DataTable
        data={Array.isArray(data) ? data : []}
        columns={columns}
        loading={isLoading}
      />
      <div className="flex items-center justify-between text-sm text-gray-500">
        <span>Halaman {page}</span>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>‹ Prev</Button>
          <Button size="sm" variant="outline" onClick={() => setPage((p) => p + 1)}>Next ›</Button>
        </div>
      </div>
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Edit Sumber' : 'Tambah Sumber'}>
        <div className="space-y-3">
          <InputField label="Kode" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} />
          <InputField label="Nama" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={form.is_active}
              onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
            />
            Aktif
          </label>
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setModalOpen(false)}>Batal</Button>
            <Button onClick={submit}>Simpan</Button>
          </div>
        </div>
      </Modal>
      <ConfirmDialog
        open={!!deleteItem}
        title="Hapus Sumber?"
        message={`Yakin hapus ${deleteItem?.name}?`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}
