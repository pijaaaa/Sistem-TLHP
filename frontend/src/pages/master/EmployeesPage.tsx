import { useState, useMemo } from 'react'
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
import { ROLE_OPTIONS } from '@/types/master'
import type { Employee } from '@/types/master'
import { isAxiosError } from 'axios'

interface FormState {
  nik: string
  name: string
  position: string
  department_id: number | ''
  role: string
  username: string
  email: string
  password: string
  password_confirmation: string
  is_active: boolean
}

const emptyForm: FormState = {
  nik: '', name: '', position: '', department_id: '', role: '',
  username: '', email: '', password: '', password_confirmation: '', is_active: true,
}

export default function EmployeesPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<Employee | null>(null)
  const [form, setForm] = useState<FormState>(emptyForm)
  const [deleteItem, setDeleteItem] = useState<Employee | null>(null)

  const params = useMemo(() => ({ page, per_page: 15 }), [page])
  const { data, isLoading } = useEmployees(params)
  const { data: departments = [] } = useDepartmentsList()
  const create = useCreateEmployee()
  const update = useUpdateEmployee()
  const remove = useDeleteEmployee()

  const openAdd = () => {
    setEditing(null)
    setForm({ ...emptyForm, department_id: departments[0]?.id ?? '' })
    setModalOpen(true)
  }
  const openEdit = (row: Employee) => {
    setEditing(row)
    setForm({
      nik: row.nik, name: row.name, position: row.position ?? '', department_id: row.department?.id ?? '',
      role: row.role ?? '', username: row.username ?? '', email: row.email ?? '',
      password: '', password_confirmation: '', is_active: row.is_active,
    })
    setModalOpen(true)
  }

  const submit = async () => {
    if (!form.nik || !form.name || !form.department_id) return
    if (!editing && (!form.role || !form.username || !form.email || !form.password)) {
      showToast('Role, username, email, dan password wajib diisi', 'error')
      return
    }
    try {
      if (editing) {
        await update.mutateAsync({
          id: editing.id, nik: form.nik, name: form.name, position: form.position,
          department_id: Number(form.department_id), is_active: form.is_active,
        })
      } else {
        await create.mutateAsync({
          ...form,
          department_id: Number(form.department_id),
          role: form.role,
          username: form.username,
          email: form.email,
          password: form.password,
          password_confirmation: form.password_confirmation,
        })
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
    { key: 'nik', header: 'NIK', body: (r) => r.nik },
    { key: 'name', header: 'Nama', body: (r) => r.name },
    { key: 'position', header: 'Jabatan', body: (r) => r.position ?? '-' },
    { key: 'role', header: 'Role', body: (r) => r.role_label ?? r.role ?? '-' },
    { key: 'department', header: 'Departemen', body: (r) => r.department?.name ?? '-' },
    { key: 'is_active', header: 'Status', body: (r) => <StatusBadge status={r.is_active ? 'Aktif' : 'Tidak Aktif'} /> },
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
        data={Array.isArray(data) ? data : []}
        columns={columns}
        loading={isLoading}
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
          <div>
            <label className="block text-sm mb-1">Role <span className="text-red-600">*</span></label>
            <Select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
              <option value="">Pilih role</option>
              {ROLE_OPTIONS.map((r) => (
                <option key={r.value} value={r.value}>{r.label}</option>
              ))}
            </Select>
          </div>
          {!editing && (
            <>
              <InputField label="Username" value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} />
              <InputField label="Email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              <InputField label="Password" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
              <InputField label="Konfirmasi Password" type="password" value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} />
            </>
          )}
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
