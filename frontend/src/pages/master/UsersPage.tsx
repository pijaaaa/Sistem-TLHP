import { useState, useMemo } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useUsers, useCreateUser, useUpdateUser, useDeleteUser } from '@/hooks/useUsers'
import { employeesApi } from '@/api/master'
import { DataTable, PageHeader, Can } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Select, Modal, ConfirmDialog, Checkbox, StatusBadge, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { ROLE_OPTIONS } from '@/types/master'
import type { AccountUser, Employee } from '@/types/master'
import { isAxiosError } from 'axios'

interface UserFormState {
  name: string
  email: string
  username: string
  role: string
  department_id: number | ''
  employee_id: number | ''
  is_active: boolean
  password: string
  password_confirmation: string
}

export default function UsersPage() {
  const { showToast } = useToast()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<AccountUser | null>(null)
  const [form, setForm] = useState<UserFormState>({
    name: '', email: '', username: '', role: 'staff_dept', department_id: '', employee_id: '',
    is_active: true, password: '', password_confirmation: '',
  })
  const [deleteItem, setDeleteItem] = useState<AccountUser | null>(null)

  const params = useMemo(() => ({ page, per_page: 15 }), [page])
  const { data, isLoading } = useUsers(params)
  const create = useCreateUser()
  const update = useUpdateUser()
  const remove = useDeleteUser()

  const { data: employeesRes } = useQuery({
    queryKey: ['employees-select'],
    queryFn: () => employeesApi.list({ per_page: 100 }),
  })
  const employees: Employee[] = Array.isArray(employeesRes) ? employeesRes : employeesRes?.data ?? []

  const openAdd = () => {
    setEditing(null)
    setForm({
      name: '', email: '', username: '', role: 'staff_dept', department_id: '', employee_id: '',
      is_active: true, password: '', password_confirmation: '',
    })
    setModalOpen(true)
  }
  const openEdit = (row: AccountUser) => {
    setEditing(row)
    setForm({
      name: row.name, email: row.email, username: row.username, role: row.role,
      department_id: row.department?.id ?? '', employee_id: row.employee?.id ?? '',
      is_active: row.is_active, password: '', password_confirmation: '',
    })
    setModalOpen(true)
  }

  const submit = async () => {
    const payload = {
      name: form.name,
      email: form.email,
      username: form.username,
      role: form.role,
      department_id: form.department_id ? Number(form.department_id) : undefined,
      employee_id: form.employee_id ? Number(form.employee_id) : undefined,
      is_active: form.is_active,
    }
    try {
      if (editing) {
        if (form.password) {
          await update.mutateAsync({ ...payload, id: editing.id, password: form.password, password_confirmation: form.password_confirmation })
        } else {
          await update.mutateAsync({ ...payload, id: editing.id })
        }
      } else {
        await create.mutateAsync({ ...payload, password: form.password, password_confirmation: form.password_confirmation })
      }
      showToast('Berhasil menyimpan pengguna', 'success')
      setModalOpen(false)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    }
  }

  const submitDelete = async () => {
    if (!deleteItem) return
    await remove.mutateAsync(deleteItem.id)
    qc.invalidateQueries({ queryKey: ['employees-select'] })
  }

  const columns: Column<AccountUser>[] = [
    { key: 'name', header: 'Nama', body: (r) => r.name },
    { key: 'email', header: 'Email', body: (r) => r.email },
    { key: 'role', header: 'Role', body: (r) => r.role_label ?? r.role },
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
        title="Pengguna"
        permissionMenu="master.employees"
        permissionAction="create"
        action={<Button onClick={openAdd}>+ Tambah Pengguna</Button>}
      />
      <DataTable
        data={Array.isArray(data) ? data : []}
        columns={columns}
        loading={isLoading}
      />
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Edit Pengguna' : 'Tambah Pengguna'}>
        <div className="space-y-3">
          <InputField label="Nama Lengkap" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <InputField label="Email" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
          <InputField label="Username" value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} />
          <div>
            <label className="block text-sm mb-1">Role</label>
            <Select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
              {ROLE_OPTIONS.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
            </Select>
          </div>
          <div>
            <label className="block text-sm mb-1">Karyawan</label>
            <Select value={form.employee_id} onChange={(e) => setForm({ ...form, employee_id: e.target.value ? Number(e.target.value) : '' })}>
              <option value="">Tidak terhubung</option>
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>{emp.name} ({emp.nik})</option>
              ))}
            </Select>
          </div>
          {!editing && (
            <>
              <InputField
                type="password"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
              />
              <InputField
                label="Konfirmasi Password"
                type="password"
                value={form.password_confirmation}
                onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
              />
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
        title="Hapus Pengguna?"
        message={`Yakin hapus ${deleteItem?.name}?`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}
