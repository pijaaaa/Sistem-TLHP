import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { ExternalLink, Target, TrendingUp, Users } from 'lucide-react'
import { useFollowUps } from '@/hooks/useFollowUps'
import { usePermission } from '@/hooks/usePermission'
import { PageHeader, CommentThread, FollowUpStatusBadge, ProgressBar, FilterChip } from '@/components/shared'
import { Select, Spinner } from '@/components/ui'
import { FOLLOW_UP_STATUS_OPTIONS } from '@/types/finding'
import { motion } from 'framer-motion'

export default function IaMonitoringPage() {
  const [status, setStatus] = useState('')
  const canComment = usePermission('ia_monitoring').create

  const params = useMemo(() => ({ per_page: 100, status: status || undefined }), [status])
  const { data, isLoading } = useFollowUps(params)

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <PageHeader
        title="Pemantauan Internal Audit"
        subtitle="Tindak lanjut yang sudah disetujui manager — tanpa komentar berarti setuju"
      />

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full md:w-64">
          <option value="">Semua Status (Disetujui ke atas)</option>
          {FOLLOW_UP_STATUS_OPTIONS.filter((s) => ['DISETUJUI', 'MENUNGGU_PERSETUJUAN_SELESAI', 'SELESAI'].includes(s.value)).map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>

        {status && (
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">Filter aktif:</span>
            <FilterChip
              label={FOLLOW_UP_STATUS_OPTIONS.find(s => s.value === status)?.label || status}
              onRemove={() => setStatus('')}
            />
          </div>
        )}
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Spinner /> Memuat tindak lanjut...
          </div>
        </div>
      ) : !data || data.data.length === 0 ? (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
          className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
        >
          <p className="text-sm text-gray-500">
            {status ? "Tidak ada tindak lanjut dengan status ini." : "Belum ada tindak lanjut yang disetujui."}
          </p>
        </motion.div>
      ) : (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-4"
        >
          {data.data.map((fu) => {
            const ap = fu.action_plan
            return (
              <motion.div
                key={fu.id}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
              >
                {/* Header */}
                <div className="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-200">
                  <div className="flex items-start justify-between gap-4 flex-wrap">
                    <div className="flex-1 min-w-0 space-y-2">
                      <div className="flex items-center gap-3 flex-wrap">
                        <Link
                          to={`/action-plan/${fu.action_plan_id}`}
                          className="inline-flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg border border-primary-200 hover:border-primary-400 hover:bg-primary-50 transition-all font-semibold text-sm text-primary-700 hover:text-primary-800 shadow-sm"
                        >
                          <ExternalLink className="w-4 h-4" />
                          {ap?.code ?? `AP #${fu.action_plan_id}`}
                        </Link>
                        <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
                      </div>
                      <p className="text-sm font-medium text-gray-900">{fu.description}</p>
                    </div>
                  </div>
                </div>

                {/* Content */}
                <div className="px-6 py-4 space-y-4">
                  {/* Info Grid */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="flex items-start gap-3">
                      <div className="flex-shrink-0 w-8 h-8 rounded-lg bg-accent-orange/10 flex items-center justify-center">
                        <Target className="w-4 h-4 text-accent-orange" />
                      </div>
                      <div>
                        <p className="text-xs text-gray-500">Target</p>
                        <p className="text-sm font-medium text-gray-900">{fu.target_date}</p>
                      </div>
                    </div>

                    <div className="flex items-start gap-3">
                      <div className="flex-shrink-0 w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
                        <TrendingUp className="w-4 h-4 text-primary-700" />
                      </div>
                      <div>
                        <p className="text-xs text-gray-500">Bobot</p>
                        <p className="text-sm font-medium text-gray-900">{fu.weight}%</p>
                      </div>
                    </div>

                    <div className="flex items-start gap-3">
                      <div className="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                        <span className="text-xs font-bold text-green-700">{fu.progress}%</span>
                      </div>
                      <div className="flex-1">
                        <p className="text-xs text-gray-500 mb-1">Progres</p>
                        <ProgressBar value={fu.progress} />
                      </div>
                    </div>

                    <div className="flex items-start gap-3">
                      <div className="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <Users className="w-4 h-4 text-blue-700" />
                      </div>
                      <div>
                        <p className="text-xs text-gray-500">PIC</p>
                        <p className="text-sm font-medium text-gray-900">
                          {(fu.assignees ?? []).map((u) => u.name).join(', ') || '-'}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Department */}
                  <div className="flex items-center gap-2 text-xs text-gray-600">
                    <span className="px-2 py-1 bg-gray-100 rounded-md font-medium">
                      {ap?.department?.name ?? '-'}
                    </span>
                  </div>

                  {/* Comments */}
                  <div className="lg:max-w-2xl pt-2 border-t border-gray-100">
                    <CommentThread
                      followUpId={fu.id}
                      canComment={canComment}
                      commentKind="DISKUSI"
                      placeholder="Komentar tanpa penghakiman = setuju..."
                    />
                  </div>
                </div>
              </motion.div>
            )
          })}
        </motion.div>
      )}
    </div>
  )
}