import { useNavigate } from 'react-router-dom'
import { useDashboard } from '@/hooks/useDashboard'
import { useHealth } from '@/hooks/useHealth'
import { PageHeader, ProgressBar } from '@/components/shared'
import { Spinner } from '@/components/ui'
import { usePermission } from '@/hooks/usePermission'
import { getFindingStatusLabel } from '@/types/finding'
import { subjectRoute } from '@/lib/links'
import type { InboxTaskItem } from '@/api/findings'
import type { NotificationItem } from '@/api/findings'

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

const WIDGET_LABELS: Record<string, string> = {
  antrian_persetujuan: 'Antrian Persetujuan',
  tl_terlambat: 'Tindak Lanjut Terlambat',
  tl_menunggu_aksi: 'Tindak Lanjut Menunggu Aksi',
  menunggu_status_eksternal: 'Menunggu Status Eksternal',
  ap_menunggu_pic: 'Action Plan Menunggu PIC',
  spi_queue: 'Antrian Review SPI',
}

const humanize = (key: string) => COUNTER_LABELS[key] ?? key.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())

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
  const navigate = useNavigate()

  const tasks = (data?.pending_tasks ?? []) as InboxTaskItem[]
  const widgets = (data?.widgets ?? {}) as Record<string, number>
  const counters = (data?.counters ?? {}) as Record<string, number>
  const followUpsByStatus = (data?.follow_ups_by_status ?? {}) as Record<string, number>

  return (
    <div className="space-y-4">
      <PageHeader title="Dashboard" subtitle="Ringkasan tindak lanjut hasil temuan audit eksternal" />

      {isLoading && <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat dashboard...</div>}
      {isError && <div className="text-red-600 text-sm">Gagal memuat: {error?.message}</div>}

      {data && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            {Object.entries(counters).map(([key, value]) => (
              <StatCard key={key} label={humanize(key)} value={value} />
            ))}
          </div>

          {Object.keys(widgets).length > 0 && (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
              {Object.entries(widgets).map(([key, value]) => (
                <div key={key} className="bg-blue-50 rounded-lg shadow-sm p-4">
                  <p className="text-sm text-blue-700">{WIDGET_LABELS[key] ?? key}</p>
                  <p className="mt-1 text-2xl font-bold text-blue-900">{value}</p>
                </div>
              ))}
            </div>
          )}

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div className="bg-white rounded-lg shadow p-4 space-y-2">
              <h2 className="text-base font-semibold">Tugas Menunggu Anda</h2>
              {tasks.length === 0 ? (
                <p className="text-sm text-gray-500">Tidak ada tugas terbuka.</p>
              ) : (
                <ul className="divide-y divide-gray-100">
                  {tasks.map((t) => (
                    <li key={t.id}>
                      <button
                        className="w-full text-left py-2 text-sm text-blue-600 hover:text-blue-700"
                        onClick={() => navigate(subjectRoute(t.subject_type, t.subject_id))}
                      >
                        <span className="font-medium">{t.title}</span>
                        <span className="text-xs text-gray-500 block">{t.task_type_label}</span>
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            <div className="bg-white rounded-lg shadow p-4 space-y-2">
              <h2 className="text-base font-semibold">Notifikasi Terbaru</h2>
              {(data.recent_notifications ?? []).length === 0 ? (
                <p className="text-sm text-gray-500">Belum ada notifikasi.</p>
              ) : (
                <ul className="divide-y divide-gray-100">
                  {(data.recent_notifications as NotificationItem[]).map((n) => (
                    <li key={n.id} className="py-2 text-sm text-gray-700">
                      {n.title}
                      <span className="text-xs text-gray-400 block">{new Date(n.created_at).toLocaleString('id-ID')}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div className="bg-white rounded-lg shadow p-4">
              <h2 className="text-base font-semibold mb-3">Temuan per Status</h2>
              {Object.keys(data.findings_by_status).length === 0 ? (
                <p className="text-sm text-gray-500">Belum ada temuan.</p>
              ) : (
                <ul className="space-y-1">
                  {Object.entries(data.findings_by_status as Record<string, number>).map(([status, total]) => (
                    <li key={status} className="flex items-center justify-between text-sm">
                      <span>{getFindingStatusLabel(status)}</span>
                      <span className="font-medium">{total}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            <div className="bg-white rounded-lg shadow p-4">
              <h2 className="text-base font-semibold mb-3">Tindak Lanjut per Status</h2>
              {Object.keys(followUpsByStatus).length === 0 ? (
                <p className="text-sm text-gray-500">Belum ada tindak lanjut.</p>
              ) : (
                <ul className="space-y-1">
                  {Object.entries(followUpsByStatus).map(([status, total]) => (
                    <li key={status} className="flex items-center gap-2 text-sm">
                      <span className="w-56 truncate capitalize">{status.toLowerCase().replace(/_/g, ' ')}</span>
                      <div className="flex-1"><ProgressBar value={(Number(total) / Math.max(1, Math.max(...Object.values(followUpsByStatus)))) * 100} showLabel={false} /></div>
                      <span className="font-medium">{total}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>

          {canExport && <p className="text-xs text-gray-400">Health: {health?.status ?? '-'}</p>}
        </>
      )}
    </div>
  )
}

export default HomePage