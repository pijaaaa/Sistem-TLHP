import { useNavigate } from 'react-router-dom'
import { useDashboard } from '@/hooks/useDashboard'
import { PageHeader, ProgressBar } from '@/components/shared'
import { Spinner, Button } from '@/components/ui'
import { useAuth } from '@/contexts/AuthContext'
import { getFindingStatusLabel } from '@/types/finding'
import { subjectRoute } from '@/lib/links'
import type { InboxTaskItem } from '@/api/findings'
import type { NotificationItem } from '@/api/findings'
import * as LucideIcons from 'lucide-react'

const COUNTER_LABELS: Record<string, string> = {
  total_temuan: 'Total Temuan',
  draft: 'Draft',
  terdaftar: 'Terdaftar',
  proses_tindak_lanjut: 'Aktif TL',
  review_spi: 'Review SPI',
  menunggu_status_eksternal: 'External Status',
  closed: 'Selesai (Closed)',
}

const WIDGET_LABELS: Record<string, string> = {
  antrian_persetujuan: 'Menunggu Persetujuan',
  tl_terlambat: 'Tindak Lanjut Terlambat',
  tl_menunggu_aksi: 'Tugas Belum Selesai',
  menunggu_status_eksternal: 'Menunggu Eksternal',
  ap_menunggu_pic: 'Belum Ada PIC',
  spi_queue: 'Antrian Review SPI',
}

const StatCard = ({ label, value, icon: Icon, color = 'primary' }: { label: string; value: number, icon: any, color?: string }) => (
  <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-5 hover:shadow-md transition-all">
    <div className={`w-12 h-12 rounded-lg flex items-center justify-center ${color === 'primary' ? 'bg-primary-50 text-primary-700' : 'bg-accent-50 text-accent-700'}`}>
      <Icon size={24} />
    </div>
    <div>
      <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">{label}</p>
      <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
    </div>
  </div>
)

const HomePage = () => {
  const { data, isLoading, isError, error } = useDashboard()
  const { user } = useAuth()
  const navigate = useNavigate()

  const tasks = (data?.pending_tasks ?? []) as InboxTaskItem[]
  const widgets = (data?.widgets ?? {}) as Record<string, number>
  const counters = (data?.counters ?? {}) as Record<string, number>
  const followUpsByStatus = (data?.follow_ups_by_status ?? {}) as Record<string, number>

  const isSPI = ['admin_spi', 'kepala_spi'].includes(user?.role || '')
  const isAuditee = ['manager_dept', 'staff_dept'].includes(user?.role || '')

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Halo, {user?.name}!</h1>
          <p className="text-gray-500 mt-1">Sistem Monitoring e-TLHT Audit Eksternal</p>
        </div>
        <div className="flex items-center gap-2">
          {isSPI && (
            <Button onClick={() => navigate('/temuan/baru')} className="rounded-xl shadow-sm">
              <LucideIcons.Plus size={18} className="mr-2" />
              Temuan Baru
            </Button>
          )}
          {isAuditee && (
            <Button onClick={() => navigate('/tindak-lanjut')} variant="outline" className="rounded-xl">
              <LucideIcons.ClipboardList size={18} className="mr-2" />
              Lapor Progres
            </Button>
          )}
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-12 text-primary-600">
          <Spinner className="w-8 h-8 mr-3" />
          <span className="font-medium">Memuat data dashboard...</span>
        </div>
      ) : isError ? (
        <div className="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
          Error: {error?.message}
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatCard label="Total Temuan" value={counters.total_temuan ?? 0} icon={LucideIcons.FileText} />
            <StatCard label="Proses Tindak Lanjut" value={counters.proses_tindak_lanjut ?? 0} icon={LucideIcons.Activity} color="accent" />
            <StatCard label="Review SPI" value={counters.review_spi ?? 0} icon={LucideIcons.Search} color="accent" />
            <StatCard label="Selesai / Closed" value={counters.closed ?? 0} icon={LucideIcons.CheckCircle2} />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div className="lg:col-span-2 space-y-6">
              <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div className="flex items-center justify-between mb-6">
                  <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <LucideIcons.Inbox size={20} className="text-primary-600" />
                    Tugas Menunggu
                  </h2>
                  <Button variant="ghost" size="sm" onClick={() => navigate('/inbox')} className="text-primary-600 hover:text-primary-700"> Lihat Semua </Button>
                </div>
                {tasks.length === 0 ? (
                  <div className="text-center py-10">
                    <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                      <LucideIcons.Check className="text-green-500" />
                    </div>
                    <p className="text-gray-500">Semua tugas sudah selesai dikerjakan!</p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {tasks.slice(0, 5).map((t) => (
                      <div 
                        key={t.id} 
                        onClick={() => navigate(subjectRoute(t.subject_type, t.subject_id))}
                        className="flex items-center justify-between p-4 rounded-xl border border-gray-50 hover:border-primary-200 hover:bg-primary-50 transition-all cursor-pointer group"
                      >
                        <div className="flex items-center gap-4">
                          <div className="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-primary-600 shadow-sm">
                            <LucideIcons.Bell size={18} />
                          </div>
                          <div>
                            <p className="text-sm font-semibold text-gray-900 group-hover:text-primary-800">{t.title}</p>
                            <p className="text-xs text-gray-500">{t.task_type_label}</p>
                          </div>
                        </div>
                        <LucideIcons.ChevronRight size={16} className="text-gray-400 group-hover:text-primary-500" />
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 className="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                  <LucideIcons.PieChart size={20} className="text-primary-600" />
                  Sebaran Progres Tindak Lanjut
                </h2>
                <div className="space-y-5">
                  {Object.entries(followUpsByStatus).map(([status, total]) => (
                    <div key={status} className="space-y-2">
                      <div className="flex justify-between text-sm">
                        <span className="font-medium text-gray-700 capitalize">{status.toLowerCase().replace(/_/g, ' ')}</span>
                        <span className="font-bold text-gray-900">{total}</span>
                      </div>
                      <ProgressBar 
                        value={(Number(total) / Math.max(1, counters.total_temuan || 10)) * 100} 
                        showLabel={false} 
                      />
                    </div>
                  ))}
                  {Object.keys(followUpsByStatus).length === 0 && <p className="text-center text-gray-500 py-6 text-sm italic">Belum ada data tindak lanjut.</p>}
                </div>
              </div>
            </div>

            <div className="space-y-6">
              <div className="bg-gradient-to-br from-primary-800 to-primary-900 rounded-2xl shadow-lg p-6 text-white">
                <h2 className="font-bold text-lg mb-4 flex items-center gap-2">
                  <LucideIcons.Zap size={20} />
                  Ringkasan Cepat
                </h2>
                <div className="grid grid-cols-1 gap-3">
                  {Object.entries(widgets).map(([key, value]) => (
                    <div key={key} className="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex justify-between items-center border border-white/10">
                      <span className="text-xs font-medium text-primary-100 uppercase tracking-wider">{WIDGET_LABELS[key] ?? key}</span>
                      <span className="text-xl font-bold">{value}</span>
                    </div>
                  ))}
                  {Object.keys(widgets).length === 0 && <p className="text-sm text-primary-200">Tidak ada item mendesak saat ini.</p>}
                </div>
              </div>

              <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 className="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                  <LucideIcons.BellRing size={20} className="text-accent-600" />
                  Notifikasi Baru
                </h2>
                <div className="space-y-4">
                  {(data?.recent_notifications as NotificationItem[] ?? []).slice(0, 5).map((n) => (
                    <div key={n.id} className="relative pl-4 border-l-2 border-accent-500 py-1">
                      <p className="text-sm font-medium text-gray-800 leading-snug">{n.title}</p>
                      <p className="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-wider">{new Date(n.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}</p>
                    </div>
                  ))}
                  {(data?.recent_notifications ?? []).length === 0 && <p className="text-center text-gray-500 text-sm">Tidak ada notifikasi baru.</p>}
                </div>
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  )
}

export default HomePage
