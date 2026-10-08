import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useFindings, useDeleteFinding } from '@/hooks/useFindings'
import { useAuditeeDepartments } from '@/hooks/useLookups'
import { DataTable, PageHeader, Can } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, StatusBadge, Select, ConfirmDialog, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import {
  FINDING_STATUS_OPTIONS,
  SOURCE_OPTIONS,
  getFindingStatusVariant,
  type Finding,
} from '@/types/finding'
import { isAxiosError } from 'axios'

export default function FindingsPage() {
  const navigate = useNavigate()
  const { showToast } = useToast()
  const createPerm = usePermission('findings')
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const [fiscalYear, setFiscalYear] = useState('')
  const [source, setSource] = useState('')
  const [departmentId, setDepartmentId] = useState('')
  const [deleteItem, setDeleteItem] = useState<Finding | null>(null)

  const { data: depts } = useAuditeeDepartments()
  const params = useMemo(
    () => ({ page, per_page: 15, status: status || undefined, fiscal_year: fiscalYear || undefined, source: source || undefined, department_id: departmentId || undefined, q: q || undefined }),
    [page, q, status, fiscalYear, source, departmentId],
  )
  const { data, isLoading } = useFindings(params)
  const remove = useDeleteFinding()

  const submitDelete = async () => {
    if (!deleteItem) return
    try {
      await remove.mutateAsync(deleteItem.id)
      showToast('Temuan berhasil dihapus.', 'success')
      setDeleteItem(null)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menghapus' : 'Gagal menghapus', 'error')
    }
  }

  const columns: Column<Finding>[] = [
    {
      key: 'registration_number',
      header: 'No. Registrasi',
      body: (r) => (r.registration_number ? <Link className="text-blue-600 hover:underline" to={`/temuan/${r.id}`}>{r.registration_number}</Link> : <span className="text-gray-400">Draft</span>),
    },
    { key: 'title', header: 'Judul', body: (r) => <Link className="text-blue-600 hover:underline" to={`/temuan/${r.id}`}>{r.title}</Link> },
    { key: 'source', header: 'Sumber', body: (r) => (r.source ? `${r.source}${r.source_name ? ' - ' + r.source_name : ''}` : '-') },
    { key: 'lhp_number', header: 'No. LHP', body: (r) => r.lhp_number ?? '-' },
    { key: 'fiscal_year', header: 'Tahun Buku', body: (r) => r.fiscal_year ?? '-' },
    { key: 'status', header: 'Status', body: (r) => <StatusBadge status={r.status_label} variant={getFindingStatusVariant(r.status)} /> },
    { key: 'age_days', header: 'Umur (hari)', body: (r) => r.age_days ?? '-' },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={() => navigate(`/temuan/${r.id}`)}>Detail</Button>
          <Can menu="findings" action="delete">
            {r.status === 'DRAFT' && (
              <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>
            )}
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Temuan"
        subtitle="Registrasi LHP auditor eksternal"
        permissionMenu="findings"
        permissionAction="create"
        action={
          createPerm.create ? (
            <Button onClick={() => navigate('/temuan/baru')}>+ Tambah Temuan</Button>
          ) : undefined
        }
      />

      <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
        <InputField placeholder="Cari judul / nomor / LHP..." value={q} onChange={(e) => { setQ(e.target.value); setPage(1) }} />
        <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }}>
          <option value="">Semua Status</option>
          {FINDING_STATUS_OPTIONS.map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
        <Select value={fiscalYear} onChange={(e) => { setFiscalYear(e.target.value); setPage(1) }}>
          <option value="">Semua Tahun</option>
          {Array.from({ length: 8 }, (_, i) => new Date().getFullYear() - i).map((y) => (
            <option key={y} value={y}>{y}</option>
          ))}
        </Select>
        <Select value={source} onChange={(e) => { setSource(e.target.value); setPage(1) }}>
          <option value="">Semua Sumber</option>
          {SOURCE_OPTIONS.map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
        <Select value={departmentId} onChange={(e) => { setDepartmentId(e.target.value); setPage(1) }}>
          <option value="">Semua Departemen</option>
          {(depts ?? []).map((d) => (
            <option key={d.id} value={d.id}>{d.name}</option>
          ))}
        </Select>
      </div>

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        loading={isLoading}
        pagination={{
          current: data?.current_page ?? 1,
          perPage: data?.per_page ?? 15,
          total: data?.total ?? 0,
          onChange: setPage,
        }}
      />

      <ConfirmDialog
        open={!!deleteItem}
        title="Hapus Temuan?"
        message={`Yakin hapus "${deleteItem?.title}"? Tindakan tidak dapat dibatalkan.`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}