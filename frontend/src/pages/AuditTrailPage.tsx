import { useState } from 'react'
import { useAuditTrail, useAuditActions } from '@/hooks/useDashboard'
import { DataTable, PageHeader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import type { Audit } from '@/api/audit'
import { Button, InputField, Select, StatusBadge } from '@/components/ui'

export default function AuditTrailPage() {
  const [page, setPage] = useState(1)
  const [action, setAction] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')

  const params = {
    page,
    per_page: 25,
    action: action || undefined,
    from: from || undefined,
    to: to || undefined,
  }

  const { data, isLoading } = useAuditTrail(params)
  const { data: actions } = useAuditActions()

  const reset = () => {
    setPage(1)
    setAction('')
    setFrom('')
    setTo('')
  }

  const columns: Column<Audit>[] = [
    {
      key: 'created_at',
      header: 'Waktu',
      body: (r) => <span className="whitespace-nowrap">{r.created_at}</span>,
    },
    {
      key: 'action',
      header: 'Aksi',
      body: (r) => <StatusBadge status={r.action} />,
    },
    {
      key: 'entity',
      header: 'Entitas',
      body: (r) =>
        r.entity_type ? `${r.entity_type}#${r.entity_id ?? '-'}` : '-',
    },
    { key: 'user', header: 'Pengguna', body: (r) => r.user?.name ?? r.user_id ?? '-' },
    { key: 'ip_address', header: 'IP', body: (r) => r.ip_address ?? '-' },
    { key: 'description', header: 'Deskripsi', body: (r) => r.description ?? '-' },
  ]

  return (
    <div className="space-y-4">
      <PageHeader title="Log Aktivitas" subtitle="Riwayat seluruh perubahan data sistem (audit trail)" />

      <div className="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
          <Select
            value={action}
            onChange={(e) => { setAction(e.target.value); setPage(1) }}
          >
            <option value="">Semua aksi</option>
            {(actions ?? []).map((a) => (
              <option key={a} value={a}>{a}</option>
            ))}
          </Select>
        </div>
        <InputField
          label="Dari Tanggal"
          type="date"
          value={from}
          onChange={(e) => { setFrom(e.target.value); setPage(1) }}
        />
        <InputField
          label="Sampai Tanggal"
          type="date"
          value={to}
          onChange={(e) => { setTo(e.target.value); setPage(1) }}
        />
        <div className="flex items-end">
          <Button variant="outline" onClick={reset}>Reset</Button>
        </div>
      </div>

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
        emptyMessage="Belum ada aktivitas tercatat"
        permissionMenu="audit_trail"
        permissionAction="view"
      />
    </div>
  )
}