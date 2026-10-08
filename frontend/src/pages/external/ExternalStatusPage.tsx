import { useState } from 'react'
import { useFindings } from '@/hooks/useFindings'
import { PageHeader, ProgressBar } from '@/components/shared'
import { StatusBadge, Spinner } from '@/components/ui'
import { getFindingStatusVariant } from '@/types/finding'
import { Link } from 'react-router-dom'

export default function ExternalStatusPage() {
  const { data, isLoading } = useFindings({ status: 'MENUNGGU_STATUS_EKSTERNAL', per_page: 50 })

  return (
    <div className="space-y-4">
      <PageHeader title="Status Eksternal" subtitle="Catat hasil auditor eksternal untuk temuan yang semua action plan-nya Sesuai" />

      {isLoading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
      ) : !data || data.data.length === 0 ? (
        <p className="text-sm text-gray-500">Belum ada temuan berstatus Menunggu Status Eksternal.</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {data.data.map((f) => (
            <Link
              key={f.id}
              to={`/status-eksternal/${f.id}`}
              className="bg-white rounded-lg shadow p-4 hover:shadow-md transition"
            >
              <div className="flex items-center justify-between gap-2">
                <span className="font-medium text-gray-800">{f.registration_number ?? `#${f.id}`}</span>
                <StatusBadge status={f.status_label} variant={getFindingStatusVariant(f.status)} />
              </div>
              <p className="text-sm text-gray-600 mt-1 line-clamp-2">{f.title}</p>
              <div className="mt-3">
                <ProgressBar value={f.progress} />
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}