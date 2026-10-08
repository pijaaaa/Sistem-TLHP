import { useDashboard } from '@/hooks/useDashboard'
import { useHealth } from '@/hooks/useHealth'
import { PageHeader } from '@/components/shared'
import { StatusBadge, Spinner } from '@/components/ui'
import { exportApi } from '@/api/audit'
import { usePermission } from '@/hooks/usePermission'
import { getFindingStatusLabel } from '@/types/finding'

const COUNTER_LABELS: Record<string, string> = {
  total_temuan: 'Total Temuan',
  draft: 'Draft',
  terdaftar: 'Terdaftar',
  proses_tindak_lanjut: 'Proses Tindak Lanjut',
  review_spi: 'Review SPI',
  menunggu_status_eksternal: 'Menunggu Status Eksternal',
  closed: 'Closed',
  total_action_plan: 'Total Action Plan',
  menunggu_penentuan_pic: 'Menunggu Penentuan PIC',
  diajukan_ke_spi: 'Diajukan ke SPI',
}

const humanize = (key: string) =>
  COUNTER_LABELS[key] ?? key.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())

const StatCard = ({ label, value }: { label: string; value: number }) => (
  <div className="bg-white rounded-lg shadow p-4">
    <p className="text-sm text-gray-500">{label}</p>
    <p className="mt-1 text-3xl font-bold text-gray-900">{value}</p>
  </div>
)

const HomePage = () => {
  const { data, isLoading, isError, error } = useDashboard()
  const { data: health } = useHealth()
  const canExport = usePermission('exports').view

  return (
    <div className="space-y-4">
      <PageHeader title="Dashboard" subtitle="Ringkasan temuan audit eksternal" />

      {isLoading && (
        <div className="flex items-center gap-2 text-sm text-gray-500">
          <Spinner /> Memuat dashboard...
        </div>
      )}

      {isError && <div className="text-red-600 text-sm">Gagal memuat: {error?.message}</div>}

      {data && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            {Object.entries(data.counters).map(([key, value]) => (
              <StatCard key={key} label={humanize(key)} value={value} />
            ))}
          </div>

          <div className="bg-white rounded-lg shadow p-4">
            <h2 className="text-base font-semibold mb-3">Temuan per Status</h2>
            {Object.keys(data.findings_by_status).length === 0 ? (
              <p className="text-sm text-gray-500">Belum ada temuan.</p>
            ) : (
              <ul className="space-y-1">
                {Object.entries(data.findings_by_status).map(([status, total]) => (
                  <li key={status} className="flex items-center justify-between text-sm">
                    <span>{getFindingStatusLabel(status)}</span>
                    <span className="font-medium">{total}</span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </>
      )}

      {canExport && (
        <div className="bg-white rounded-lg shadow p-4">
          <h2 className="text-base font-semibold mb-3">Ekspor Data</h2>
          <div className="flex flex-wrap gap-3">
            <a className="text-sm text-blue-600 hover:underline" href={exportApi.url.findings}>
              Unduh Temuan (CSV)
            </a>
            <a className="text-sm text-blue-600 hover:underline" href={exportApi.url.actionPlans}>
              Unduh Rencana Aksi (CSV)
            </a>
            <a className="text-sm text-blue-600 hover:underline" href={exportApi.url.auditTrail}>
              Unduh Audit Trail (CSV)
            </a>
          </div>
        </div>
      )}

      {health && (
        <div className="bg-white rounded-lg shadow p-4">
          <h2 className="text-base font-semibold mb-3">Status Sistem</h2>
          <div className="flex flex-wrap gap-4 text-sm">
            <span>API: <StatusBadge status={health.data.status} /></span>
            <span>Database: <StatusBadge status={health.data.database} /></span>
            <span className="text-gray-500">
              {health.data.app} v{health.data.version}
            </span>
          </div>
        </div>
      )}
    </div>
  )
}

export default HomePage