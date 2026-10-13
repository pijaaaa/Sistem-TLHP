import { useNavigate } from 'react-router-dom'
import { useDashboard } from '@/hooks/useDashboard'
import { useAuth } from '@/contexts/AuthContext'
import { StatCard, EmptyState } from '@/components/shared'
import { SkeletonCard, Badge } from '@/components/ui'
import { getFindingStatusLabel } from '@/types/finding'
import { subjectRoute } from '@/lib/links'
import type { InboxTaskItem } from '@/api/findings'
import type { NotificationItem } from '@/api/findings'
import { motion } from 'framer-motion'
import { 
  FileText, 
  ClipboardList, 
  CheckCircle2, 
  Clock, 
  AlertCircle,
  Bell,
  ChevronRight,
  Inbox,
  AlertTriangle
} from 'lucide-react'

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

const COUNTER_ICONS: Record<string, any> = {
  total_temuan: FileText,
  total_action_plan: ClipboardList,
  closed: CheckCircle2,
  draft: FileText,
  terdaftar: FileText,
  proses_tindak_lanjut: Clock,
  review_spi: CheckCircle2,
  menunggu_status_eksternal: Clock,
  menunggu_penentuan_pic: Clock,
  diajukan_ke_spi: ClipboardList,
}

const WIDGET_CONFIG: Record<string, { label: string; color: string; bgColor: string; icon: any }> = {
  antrian_persetujuan: { 
    label: 'Antrian Persetujuan', 
    color: 'text-blue-700', 
    bgColor: 'bg-blue-50 border-blue-200',
    icon: Clock
  },
  tl_terlambat: { 
    label: 'Tindak Lanjut Terlambat', 
    color: 'text-red-700', 
    bgColor: 'bg-red-50 border-red-200',
    icon: AlertTriangle
  },
  tl_menunggu_aksi: { 
    label: 'Menunggu Aksi Anda', 
    color: 'text-yellow-700', 
    bgColor: 'bg-yellow-50 border-yellow-200',
    icon: AlertCircle
  },
  menunggu_status_eksternal: { 
    label: 'Menunggu Status Eksternal', 
    color: 'text-purple-700', 
    bgColor: 'bg-purple-50 border-purple-200',
    icon: Clock
  },
  ap_menunggu_pic: { 
    label: 'Action Plan Menunggu PIC', 
    color: 'text-orange-700', 
    bgColor: 'bg-orange-50 border-orange-200',
    icon: AlertCircle
  },
  spi_queue: { 
    label: 'Antrian Review SPI', 
    color: 'text-green-700', 
    bgColor: 'bg-green-50 border-green-200',
    icon: ClipboardList
  },
}

const humanize = (key: string) => COUNTER_LABELS[key] ?? key.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())

const HomePage = () => {
  const { data, isLoading, isError, error } = useDashboard()
  const { user } = useAuth()
  const navigate = useNavigate()

  const tasks = (data?.pending_tasks ?? []) as InboxTaskItem[]
  const widgets = (data?.widgets ?? {}) as Record<string, number>
  const counters = (data?.counters ?? {}) as Record<string, number>
  const followUpsByStatus = (data?.follow_ups_by_status ?? {}) as Record<string, number>

  return (
    <motion.div 
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      className="space-y-6 max-w-7xl mx-auto pb-10"
    >
      {/* Welcome Header */}
      <div className="bg-gradient-to-r from-[#1a4d3c] to-[#2d6a5a] rounded-2xl shadow-soft p-8 text-white relative overflow-hidden">
        <div className="absolute right-0 top-0 translate-x-1/4 -translate-y-1/4 w-96 h-96 bg-white/5 rounded-full pointer-events-none"></div>
        <div className="flex items-center justify-between relative z-10">
          <div>
            <h1 className="text-3xl font-bold mb-2">
              Selamat datang, {user?.name || 'User'} 👋
            </h1>
            <p className="text-primary-100 max-w-xl text-sm leading-relaxed">
              {data?.role_label || 'Dashboard'} — Kelola dan pantau tindak lanjut hasil temuan audit eksternal secara terpusat dan efisien.
            </p>
          </div>
          <div className="flex gap-3">
          </div>
        </div>
      </div>

      {isLoading && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[...Array(4)].map((_, i) => (
            <SkeletonCard key={i} />
          ))}
        </div>
      )}

      {isError && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
          <p className="font-medium">Gagal memuat dashboard</p>
          <p className="text-sm">{error?.message}</p>
        </div>
      )}

      {data && (
        <>
          {/* Stats Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {Object.entries(counters).map(([key, value]) => {
              const IconComponent = COUNTER_ICONS[key] || FileText
              return (
                <StatCard 
                  key={key} 
                  label={humanize(key)} 
                  value={value}
                  icon={IconComponent}
                  iconBgColor="bg-primary-50"
                  iconColor="text-primary-600"
                />
              )
            })}
          </div>

          {/* Widget Alerts */}
          {Object.keys(widgets).length > 0 && (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {Object.entries(widgets).map(([key, value]) => {
                const config = WIDGET_CONFIG[key] || { 
                  label: key, 
                  color: 'text-gray-700', 
                  bgColor: 'bg-gray-50 border-gray-200',
                  icon: AlertCircle
                }
                const IconComponent = config.icon
                
                return (
                  <motion.div 
                    whileHover={{ scale: 1.02 }}
                    key={key} 
                    className={`rounded-2xl border-2 shadow-soft p-5 transition-all duration-200 cursor-pointer ${config.bgColor}`}
                  >
                    <div className="flex items-center gap-4">
                      <div className={`rounded-xl p-3 bg-white/50 backdrop-blur-sm`}>
                        <IconComponent size={24} className={config.color} />
                      </div>
                      <div className="flex-1">
                        <p className={`text-xs font-bold uppercase tracking-wider ${config.color} opacity-80`}>{config.label}</p>
                        <p className={`mt-0.5 text-3xl font-black ${config.color}`}>{value}</p>
                      </div>
                    </div>
                  </motion.div>
                )
              })}
            </div>
          )}

          {/* Two Column Layout */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Tugas Menunggu */}
            <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <Inbox className="text-accent-orange" size={20} />
                  Tugas Menunggu Anda
                </h2>
                {tasks.length > 0 && (
                  <Badge variant="warning">{tasks.length} tugas</Badge>
                )}
              </div>
              {tasks.length === 0 ? (
                <EmptyState 
                  icon={Inbox}
                  title="Tidak ada tugas"
                  description="Semua tugas telah selesai"
                />
              ) : (
                <div className="space-y-3">
                  {tasks.map((t) => (
                    <motion.button
                      whileHover={{ x: 5 }}
                      key={t.id}
                      className="w-full text-left p-4 rounded-xl border border-gray-100 hover:border-primary-200 hover:bg-primary-50/50 transition-all duration-200 group"
                      onClick={() => navigate(subjectRoute(t.subject_type, t.subject_id))}
                    >
                      <div className="flex items-start justify-between">
                        <div>
                          <p className="font-semibold text-gray-900 group-hover:text-primary-700">{t.title}</p>
                          <p className="text-xs text-gray-500 mt-1">{t.task_type_label}</p>
                        </div>
                        <ChevronRight size={18} className="text-gray-400 group-hover:text-primary-600" />
                      </div>
                    </motion.button>
                  ))}
                </div>
              )}
            </div>

            {/* Notifikasi */}
            <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <Bell className="text-accent-orange" size={20} />
                  Notifikasi Terbaru
                </h2>
              </div>
              {(data.recent_notifications ?? []).length === 0 ? (
                <EmptyState 
                  icon={Bell}
                  title="Belum ada notifikasi"
                  description="Notifikasi akan muncul di sini"
                />
              ) : (
                <div className="space-y-3">
                  {(data.recent_notifications as NotificationItem[]).map((n) => (
                    <div key={n.id} className="p-4 rounded-xl bg-gray-50 border border-gray-100">
                      <p className="text-sm text-gray-900 font-medium">{n.title}</p>
                      <p className="text-xs text-gray-400 mt-1.5">
                        {new Date(n.created_at).toLocaleString('id-ID')}
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Charts Row */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Temuan per Status */}
            <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-6">Status Temuan Audit</h2>
              {Object.keys(data.findings_by_status).length === 0 ? (
                <EmptyState 
                  icon={FileText}
                  title="Belum ada temuan"
                  description="Data temuan akan ditampilkan di sini"
                />
              ) : (
                <div className="space-y-4">
                  {Object.entries(data.findings_by_status as Record<string, number>).map(([status, total]) => (
                    <div key={status} className="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                      <div className="flex items-center gap-3">
                         <div className={`w-2 h-2 rounded-full ${total > 0 ? 'bg-primary-600' : 'bg-gray-300'}`}></div>
                         <span className="text-sm font-medium text-gray-700">{getFindingStatusLabel(status)}</span>
                      </div>
                      <span className="font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded-full text-sm">{total}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Tindak Lanjut per Status */}
            <div className="bg-white rounded-2xl shadow-soft border border-gray-100 p-6">
              <h2 className="text-lg font-bold text-gray-900 mb-6">Progres Tindak Lanjut</h2>
              {Object.keys(followUpsByStatus).length === 0 ? (
                <EmptyState 
                  icon={ClipboardList}
                  title="Belum ada tindak lanjut"
                  description="Data tindak lanjut akan ditampilkan di sini"
                />
              ) : (
                <div className="space-y-6">
                  {Object.entries(followUpsByStatus).map(([status, total]) => (
                    <div key={status} className="space-y-2">
                      <div className="flex items-center justify-between text-sm">
                        <span className="font-medium text-gray-700 capitalize">{status.toLowerCase().replace(/_/g, ' ')}</span>
                        <span className="font-bold text-primary-700">{total}</span>
                      </div>
                      <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                         <motion.div 
                           initial={{ width: 0 }}
                           animate={{ width: `${(Number(total) / Math.max(1, Math.max(...Object.values(followUpsByStatus)))) * 100}%` }}
                           transition={{ duration: 1, ease: "easeOut" }}
                           className="h-full bg-accent-orange rounded-full"
                         />
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </motion.div>
  )
}

export default HomePage