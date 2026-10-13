import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { CheckCircle2, AlertCircle } from 'lucide-react'
import { useFollowUps, useSubmitFollowUps } from '@/hooks/useFollowUps'
import { Can, FollowUpStatusBadge, ProgressBar, FollowUpDetailModal, FilterChip, EmptyState } from '@/components/shared'
import { Button, Select, Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { FOLLOW_UP_STATUS_OPTIONS, type FollowUp } from '@/types/finding'
import { isAxiosError } from 'axios'
import { motion } from 'framer-motion'

export default function FollowUpsPage() {
  const { showToast } = useToast()
  const [status, setStatus] = useState('')
  const [detailFu, setDetailFu] = useState<FollowUp | null>(null)

  const params = useMemo(() => ({ per_page: 100, status: status || undefined }), [status])
  const { data, isLoading } = useFollowUps(params)
  const submit = useSubmitFollowUps()

  const groups = useMemo(() => {
    const items = data?.data ?? []
    const map = new Map<number, FollowUp[]>()
    for (const fu of items) {
      const key = fu.action_plan_id
      if (!map.has(key)) map.set(key, [])
      map.get(key)!.push(fu)
    }
    return [...map.entries()]
  }, [data])

  const doSubmit = async (fu: FollowUp) => {
    try {
      await submit.mutateAsync([fu.id])
      showToast('Tindak lanjut diajukan.', 'success')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengajukan' : 'Gagal mengajukan', 'error')
    }
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Tindak Lanjut</h1>
        <p className="text-sm text-gray-600 mt-1">Kegiatan yang disusun PIC untuk menjalankan action plan</p>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
        <Select value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">Semua Status</option>
          {FOLLOW_UP_STATUS_OPTIONS.map((s) => (
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

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Spinner /> Memuat tindak lanjut...
          </div>
        </div>
      ) : groups.length === 0 ? (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
        >
          <EmptyState
            icon={AlertCircle}
            title="Tidak ada tindak lanjut"
            description={status ? "Tidak ada tindak lanjut dengan status ini." : "Belum ada tindak lanjut yang terdaftar."}
          />
        </motion.div>
      ) : (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-4"
        >
          {groups.map(([apId, items]) => {
            const ap = items[0].action_plan
            const completedCount = items.filter(f => f.status === 'SELESAI').length
            const totalCount = items.length
            const progressPercent = Math.round((completedCount / totalCount) * 100)

            return (
              <div key={apId} className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                {/* Header */}
                <div className="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-200 flex items-center justify-between flex-wrap gap-4">
                  <div className="flex-1">
                    <Link 
                      className="text-primary-600 hover:text-primary-700 font-semibold text-lg transition-colors" 
                      to={`/action-plan/${apId}`}
                    >
                      {ap?.code ?? `AP #${apId}`}
                    </Link>
                    <p className="text-sm text-gray-700 mt-1">{ap?.title}</p>
                    <div className="flex items-center gap-3 mt-2 text-xs text-gray-600">
                      <span>Status: <strong>{ap?.status_label}</strong></span>
                      <span>•</span>
                      <span>TL Selesai: <strong>{completedCount}/{totalCount}</strong> ({progressPercent}%)</span>
                    </div>
                  </div>
                  <Can menu="follow_ups" action="create">
                    {ap?.status === 'PROSES_TINDAK_LANJUT' && (
                      <Link
                        to={`/action-plan/${apId}/tindak-lanjut/susun`}
                        className="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg bg-accent-orange text-white hover:bg-accent-orange/90 transition-colors"
                      >
                        Susun Tindak Lanjut
                      </Link>
                    )}
                  </Can>
                </div>

                {/* Table */}
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="bg-gray-50 border-b border-gray-200">
                        <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Uraian</th>
                        <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Target</th>
                        <th className="text-center px-6 py-3 text-xs font-semibold text-gray-700">Bobot</th>
                        <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Progres</th>
                        <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Status</th>
                        <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">PIC</th>
                        <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Aksi</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {items.map((fu) => (
                        <tr key={fu.id} className="hover:bg-gray-50 transition-colors">
                          <td className="px-6 py-4 text-sm text-gray-900">{fu.description}</td>
                          <td className="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{fu.target_date}</td>
                          <td className="px-6 py-4 text-center">
                            <span className="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary-100 text-xs font-medium text-primary-700">
                              {fu.weight}%
                            </span>
                          </td>
                          <td className="px-6 py-4">
                            <div className="w-32"><ProgressBar value={fu.progress} /></div>
                          </td>
                          <td className="px-6 py-4">
                            <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
                          </td>
                          <td className="px-6 py-4 text-sm text-gray-700">
                            {(fu.assignees ?? []).map((u) => u.name).join(', ') || '-'}
                          </td>
                          <td className="px-6 py-4">
                            <div className="flex gap-2 flex-wrap">
                              <Button 
                                size="sm" 
                                variant="outline" 
                                onClick={() => setDetailFu(fu)}
                              >
                                Detail
                              </Button>
                              {(fu.status === 'DRAFT' || fu.status === 'REVISI') && (
                                <Button 
                                  size="sm" 
                                  variant="secondary" 
                                  onClick={() => doSubmit(fu)}
                                >
                                  Ajukan
                                </Button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )
          })}
        </motion.div>
      )}

      <FollowUpDetailModal followUp={detailFu} onClose={() => setDetailFu(null)} />
    </div>
  )
}