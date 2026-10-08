import { useState, useMemo } from 'react'
import { useDepartments, useCreateDepartment, useUpdateDepartment, useDeleteDepartment } from '@/hooks/useDepartments'
import { DataTable, PageHeader, Can } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, ConfirmDialog, Checkbox, StatusBadge, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { Department } from '@/types/master'
import { isAxiosError } from 'axios'

export default function DepartmentsPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<Department | null>(null)
  const [form, setForm] = useState({ code: '', name: '', is_active: true })
  const [deleteItem, setDeleteItem] = useState<Department | null>(null)

  const params = useMemo(() => ({ page, per_page: 15 }), [page])
  const { data, isLoading } = useDepartments(params)
  const create = useCreateDepartment()
  const update = useUpdateDepartment()
  const remove = useDeleteDepartment()

  const openAdd = () => {
    setEditing(null)
    setForm({ code: '', name: '', is_active: true })
    setModalOpen(true)
  }
  const openEdit = (row: Department) => {
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
      showToast('Berhasil menyimpan departemen', 'success')
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

  const columns: Column<Department>[] = [
    { key: 'code', header: 'Kode', body: (r) => r.code },
    { key: 'name', header: 'Nama', body: (r) => r.name },
    { key: 'is_active', header: 'Status', body: (r) => <StatusBadge status={r.is_active ? 'Aktif' : 'Tidak Aktif'} /> },
    {
      key: 'actions', header: 'Aksi', body: (r) => (
        <div className="flex gap-2">
          <Can menu="master.departments" action="update">
            <Button size="sm" variant="outline" onClick={() => openEdit(r)}>Edit</Button>
          </Can>
          <Can menu="master.departments" action="delete">
            <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Departemen"
        permissionMenu="master.departments"
        permissionAction="create"
        action={<Button onClick={openAdd}>+ Tambah Departemen</Button>}
      />
      <DataTable
        data={Array.isArray(data) ? data : []}
        columns={columns}
        loading={isLoading}
      />
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Edit Departemen' : 'Tambah Departemen'}>
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
        title="Hapus Departemen?"
        message={`Yakin hapus ${deleteItem?.name}?`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}
