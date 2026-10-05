import { useState } from 'react'
import {
  useEmployees,
  useDepartmentsList,
  useCreateEmployee,
  useUpdateEmployee,
  useDeleteEmployee,
} from '@/hooks/useEmployees'
import { DataTable, PageHeader, Can } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Select, Modal, ConfirmDialog, Checkbox, StatusBadge, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { Employee } from '@/types/master'
import { isAxiosError } from 'axios'

interface FormState {
  nik: string
  name: string
  position: string
  department_id: number | ''
  is_active: boolean
}

export default function EmployeesPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<Employee | null>(null)
  const [form, setForm] = useState<FormState>({ nik: '', name: '', position: '', department_id: '', is_active: true })
  const [deleteItem, setDeleteItem] = useState<Employee | null>(null)

  const { data, isLoading } = useEmployees({ page, per_page: 15 })
  const { data: departments = [] } = useDepartmentsList()
  const create = useCreateEmployee()
  const update = useUpdateEmployee()
  const remove = useDeleteEmployee()

  const openAdd = () => {
    setEditing(null)
    setForm({ nik: '', name: '', position: '', department_id: departments[0]?.id ?? '', is_active: true })
    setModalOpen(true)
  }
  const openEdit = (row: Employee) => {
    setEditing(row)
    setForm({ nik: row.nik, name: row.name, position: row.position ?? '', department_id: row.department?.id ?? '', is_active: row.is_active })
    setModalOpen(true)
  }

  const submit = async () => {
    if (!form.nik || !form.name || !form.department_id) return
    try {
      if (editing) {
        await update.mutateAsync({ id: editing.id, ...form, department_id: Number(form.department_id) })
      } else {
        await create.mutateAsync({ ...form, department_id: Number(form.department_id) })
      }
      showToast('Berhasil menyimpan karyawan', 'success')
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

  const columns: Column<Employee>[] = [
    { key: 'nik', header: 'NIK', body: (r) => r.nik },
    { key: 'name', header: 'Nama', body: (r) => r.name },
    { key: 'position', header: 'Jabatan', body: (r) => r.position ?? '-' },
    { key: 'department', header: 'Departemen', body: (r) => r.department?.name ?? '-' },
    { key: 'is_active', header: 'Status', body: (r) => <StatusBadge status={r.is_active ? 'Aktif' : 'Tidak Aktif'} /> },
    {
      key: 'actions', header: 'Aksi', body: (r) => (
        <div className="flex gap-2">
          <Can menu="master.employees" action="update">
            <Button size="sm" variant="outline" onClick={() => openEdit(r)}>Edit</Button>
          </Can>
          <Can menu="master.employees" action="delete">
            <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Karyawan"
        permissionMenu="master.employees"
        permissionAction="create"
        action={<Button onClick={openAdd}>+ Tambah Karyawan</Button>}
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
      />
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Edit Karyawan' : 'Tambah Karyawan'}>
        <div className="space-y-3">
          <InputField label="NIK" value={form.nik} onChange={(e) => setForm({ ...form, nik: e.target.value })} />
          <InputField label="Nama" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <InputField label="Jabatan" value={form.position} onChange={(e) => setForm({ ...form, position: e.target.value })} />
          <div>
            <label className="block text-sm mb-1">Departemen</label>
            <Select
              value={form.department_id}
              onChange={(e) => setForm({ ...form, department_id: e.target.value ? Number(e.target.value) : '' })}
            >
              <option value="">Pilih departemen</option>
              {departments.map((d) => (
                <option key={d.id} value={d.id}>{d.name}</option>
              ))}
            </Select>
          </div>
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
        title="Hapus Karyawan?"
        message={`Yakin hapus ${deleteItem?.name}?`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}
