import { useState } from 'react'
import { useFindings } from '@/hooks/useFindings'
import { DataTable, PageHeader } from '@/components/shared'
import { StatusBadge } from '@/components/ui'
import type { Column } from '@/components/shared/data-table'
import type { Finding } from '@/types/finding'
import { getFindingStatusLabel } from '@/types/finding'

export default function FindingsPage() {
  const [page, setPage] = useState(1)

  const { data, isLoading } = useFindings({ page, per_page: 15 })

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
    { key: 'auditor_action_plan', header: 'Rencana Aksi Auditor', body: (r) => r.auditor_action_plan ?? '-' },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Daftar Temuan"
        subtitle="Daftar temuan audit eksternal"
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
        permissionMenu="findings.list"
        permissionAction="view"
      />
    </div>
  )
}
