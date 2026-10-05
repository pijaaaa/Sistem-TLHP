import { useState, useEffect } from 'react'
import { useAccessMenus, useRoleMatrix, useUpdateRolePermissions, useUserPermissionMatrix, useUpdateUserPermissions } from '@/hooks/usePermissions'
import { PageHeader } from '@/components/shared'
import { Button, Checkbox } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { MenuOption, PermissionMatrix, OverrideMatrix } from '@/types/master'
import { isAxiosError } from 'axios'

type PermAction = 'view' | 'create' | 'update' | 'delete'
const ACTIONS: { key: PermAction; label: string }[] = [
  { key: 'view', label: 'Lihat' },
  { key: 'create', label: 'Tambah' },
  { key: 'update', label: 'Ubah' },
  { key: 'delete', label: 'Hapus' },
]

function RoleMatrixEditor() {
  const { showToast } = useToast()
  const { data: access, isLoading: accessLoading } = useAccessMenus()
  const [role, setRole] = useState<string>('staff_dept')
  const { data: rdata, isLoading } = useRoleMatrix(role)
  const mutate = useUpdateRolePermissions()

  const [draft, setDraft] = useState<Record<string, PermissionMatrix>>({})

  useEffect(() => {
    if (rdata?.matrix) setDraft(rdata.matrix)
  }, [rdata?.matrix])

  const toggle = (code: string, action: keyof PermissionMatrix) => {
    setDraft((prev) => ({
      ...prev,
      [code]: { ...prev[code], [action]: !prev[code]?.[action] },
    }))
  }

  const save = async () => {
    try {
      await mutate.mutateAsync({ role, permissions: draft })
      showToast('Izin role berhasil disimpan', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    }
  }

  if (accessLoading) return <p>Memuat menu...</p>
  if (!rdata) return null

  return (
    <div className="space-y-4">
      <div className="flex items-end gap-4">
        <div>
          <label className="block text-sm mb-1">Role</label>
          <select
            className="border rounded px-2 py-1"
            value={role}
            onChange={(e) => setRole(e.target.value)}
          >
            {(access?.roles ?? []).map((r) => (
              <option key={r.value} value={r.value}>{r.label}</option>
            ))}
          </select>
        </div>
        <Button onClick={save} disabled={mutate.isPending}>Simpan Izin Role</Button>
      </div>
      {isLoading ? <p>Memuat...</p> : (
        <div className="overflow-x-auto border rounded">
          <table className="w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="text-left p-2">Menu</th>
                {ACTIONS.map((a) => <th key={a.key} className="p-2">{a.label}</th>)}
              </tr>
            </thead>
            <tbody>
              {rdata.menus.map((m: MenuOption) => {
                const row = draft[m.code] ?? { view: false, create: false, update: false, delete: false }
                return (
                  <tr key={m.id} className="border-t">
                    <td className="p-2">{m.name}</td>
                    {ACTIONS.map((a) => (
                      <td key={a.key} className="p-2 text-center">
                        <Checkbox
                          checked={!!row[a.key as keyof OverrideMatrix]}
                          onChange={() => toggle(m.code, a.key)}
                          disabled={!row.view && a.key !== 'view'}
                        />
                      </td>
                    ))}
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}

function UserOverrideEditor() {
  const { showToast } = useToast()
  const [userId, setUserId] = useState('')
  const { data: udata, isLoading, refetch } = useUserPermissionMatrix(userId ? Number(userId) : 0)
  const mutate = useUpdateUserPermissions()

  const [draft, setDraft] = useState<Record<string, OverrideMatrix>>({})

  useEffect(() => {
    if (udata?.overrides) setDraft(udata.overrides)
  }, [udata?.overrides])

  const setCell = (code: string, action: keyof OverrideMatrix, value: OverrideMatrix[string]) => {
    setDraft((prev) => ({ ...prev, [code]: { ...prev[code], [action]: value } }))
  }

  const save = async () => {
    if (!userId) return
    try {
      await mutate.mutateAsync({ userId: Number(userId), permissions: draft })
      showToast('Override izin pengguna berhasil disimpan', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    }
  }

  const cellValue = (v: OverrideMatrix[string]): 'inherit' | 'allow' | 'deny' =>
    v === null ? 'inherit' : v ? 'allow' : 'deny'

  return (
    <div className="space-y-4">
      <div className="flex items-end gap-4">
        <div>
          <label className="block text-sm mb-1">ID Pengguna</label>
          <input
            type="number"
            className="border rounded px-2 py-1 w-32"
            value={userId}
            onChange={(e) => setUserId(e.target.value)}
            placeholder="Masukkan ID"
          />
        </div>
        {userId && <Button variant="outline" onClick={() => refetch()}>Cek</Button>}
      </div>

      {!udata ? (
        <p className="text-sm text-gray-500">Masukkan ID pengguna untuk mengatur override izin.</p>
      ) : (
        <div className="space-y-4">
          <p className="text-sm">Role: <strong>{udata.role}</strong></p>
          <Button onClick={save} disabled={mutate.isPending}>Simpan Override</Button>
          {isLoading ? <p>Memuat...</p> : (
            <div className="overflow-x-auto border rounded">
              <table className="w-full text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="text-left p-2">Menu</th>
                    {ACTIONS.map((a) => <th key={a.key} className="p-2">{a.label}</th>)}
                  </tr>
                </thead>
                <tbody>
                  {udata.menus.map((m: MenuOption) => {
                    const row = draft[m.code] ?? { view: null, create: null, update: null, delete: null }
                    return (
                      <tr key={m.id} className="border-t">
                        <td className="p-2">{m.name}</td>
                        {ACTIONS.map((a) => (
                          <td key={a.key} className="p-2">
                            <select
                              className="border rounded px-1 py-0.5 w-full"
                              value={cellValue(row[a.key as keyof OverrideMatrix])}
                              onChange={(e) => {
                                const v = e.target.value
                                const map: Record<string, OverrideMatrix[string]> = {
                                  inherit: null, allow: true, deny: false,
                                }
                                setCell(m.code, a.key, map[v])
                              }}
                            >
                              <option value="inherit">Wariskan</option>
                              <option value="allow">Izinkan</option>
                              <option value="deny">Tolak</option>
                            </select>
                          </td>
                        ))}
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  )
}

export default function PermissionsPage() {
  const [tab, setTab] = useState<'role' | 'user'>('role')

  return (
    <div className="space-y-4">
      <PageHeader title="Manajemen Akses" permissionMenu="access.permissions" permissionAction="view" />
  <div className="flex gap-2 border-b mb-4">
        <button
          onClick={() => setTab('role')}
          className={tab === 'role' ? 'border-b-2 border-blue-600 px-3 py-1' : 'px-3 py-1 text-gray-500'}
        >
          Izin per Role
        </button>
        <button
          onClick={() => setTab('user')}
          className={tab === 'user' ? 'border-b-2 border-blue-600 px-3 py-1' : 'px-3 py-1 text-gray-500'}
        >
          Override per Pengguna
        </button>
      </div>
      {tab === 'role' ? <RoleMatrixEditor /> : <UserOverrideEditor />}
    </div>
  )
}
