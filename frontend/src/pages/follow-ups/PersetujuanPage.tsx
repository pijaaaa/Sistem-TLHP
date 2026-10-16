import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { ExternalLink, ChevronDown, ChevronUp, MessageCircle, Target, Users } from 'lucide-react'
import { useFollowUps, useFollowUpComments } from '@/hooks/useFollowUps'
import { PageHeader, ReviewActionBar, ReviewTimeline, FollowUpStatusBadge, CommentThread, Tabs } from '@/components/shared'
import { Button, Spinner } from '@/components/ui'
import { useAuth } from '@/contexts/AuthContext'
import { motion } from 'framer-motion'
import type { FollowUp } from '@/types/finding'

const FollowUpRow = ({ fu, isManager }: { fu: FollowUp; isManager: boolean }) => {
  const [showTimeline, setShowTimeline] = useState(false)
  const { data: comments } = useFollowUpComments(fu.id)
  const hasIaComment = (comments ?? []).some((c) => c.kind === 'IA_COMMENT')

  return (
    <div className="border border-gray-200 rounded-lg overflow-hidden hover:border-primary-300 transition-colors">
      <div className="p-4 space-y-3 bg-white">
        <div className="flex items-start justify-between gap-3 flex-wrap">
          <div className="flex-1 min-w-0 space-y-2">
            <p className="text-sm font-medium text-gray-900">{fu.description}</p>
            <div className="flex items-center gap-4 flex-wrap text-xs text-gray-600">
              <div className="flex items-center gap-1.5">
                <Target className="w-3.5 h-3.5 text-accent-orange" />
                <span>Target: <strong>{fu.target_date}</strong></span>
              </div>
              <div className="flex items-center gap-1.5">
                <span className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-xs font-medium text-primary-700">
                  {fu.weight}%
                </span>
                <span>Bobot</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Users className="w-3.5 h-3.5 text-blue-600" />
                <span>{(fu.assignees ?? []).map((u) => u.name).join(', ') || '-'}</span>
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2 shrink-0 flex-wrap">
            <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
            {hasIaComment && (
              <div className="flex items-center gap-1 px-2 py-1 bg-blue-50 border border-blue-200 rounded text-xs font-medium text-blue-700">
                <MessageCircle className="w-3 h-3" />
                Komentar IA
              </div>
            )}
          </div>
        </div>

        {isManager && (
          <ReviewActionBar
            followUp={fu}
            canDecide={fu.status === 'DIAJUKAN' || fu.status === 'MENUNGGU_PERSETUJUAN_SELESAI'}
          />
        )}

        <div className="flex gap-2 pt-2 border-t border-gray-100">
          <Button 
            size="sm" 
            variant="outline" 
            onClick={() => setShowTimeline(!showTimeline)}
            className="flex items-center gap-2"
          >
            {showTimeline ? (
              <>
                <ChevronUp className="w-4 h-4" />
                Tutup Riwayat
              </>
            ) : (
              <>
                <ChevronDown className="w-4 h-4" />
                Riwayat Keputusan
              </>
            )}
          </Button>
        </div>
      </div>

      {showTimeline && (
        <motion.div
          initial={{ opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: 'auto' }}
          exit={{ opacity: 0, height: 0 }}
          className="bg-gray-50 border-t border-gray-200 p-4"
        >
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div>
              <h4 className="text-sm font-semibold text-gray-700 mb-2">Riwayat Keputusan</h4>
              <ReviewTimeline followUpId={fu.id} />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-gray-700 mb-2">Diskusi</h4>
              <CommentThread followUpId={fu.id} commentKind="DISKUSI" />
            </div>
          </div>
        </motion.div>
      )}
    </div>
  )
}

export default function PersetujuanPage() {
  const { user } = useAuth()
  const [tab, setTab] = useState('tindak-lanjut')

  const params = useMemo(() => ({ per_page: 100 }), [])
  const { data, isLoading } = useFollowUps(params)

  const groups = useMemo(() => {
    const map = new Map<number, FollowUp[]>()
    for (const fu of data?.data ?? []) {
      if (!map.has(fu.action_plan_id)) map.set(fu.action_plan_id, [])
      map.get(fu.action_plan_id)!.push(fu)
    }
    return [...map.entries()]
  }, [data])

  const pendingParams = useMemo(() => ({ per_page: 100, status: 'MENUNGGU_PERSETUJUAN_SELESAI' }), [])
  const { data: pendingData, isLoading: pendingLoading } = useFollowUps(pendingParams)

  const pendingGroups = useMemo(() => {
    const map = new Map<number, FollowUp[]>()
    for (const fu of pendingData?.data ?? []) {
      if (!map.has(fu.action_plan_id)) map.set(fu.action_plan_id, [])
      map.get(fu.action_plan_id)!.push(fu)
    }
    return [...map.entries()]
  }, [pendingData])

  const isManager = user?.role === 'manager_dept' || user?.role === 'manager_ia'

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <PageHeader title="Persetujuan Manager" subtitle="Keputusan per tindak lanjut yang diajukan PIC" />

      <Tabs
        tabs={[
          { key: 'tindak-lanjut', label: 'Tindak Lanjut' },
          { key: 'penyelesaian', label: 'Penyelesaian' },
        ]}
        active={tab}
        onChange={setTab}
      />

      {tab === 'penyelesaian' && (
        pendingLoading ? (
          <div className="flex items-center justify-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div className="flex items-center gap-2 text-sm text-gray-500">
              <Spinner /> Memuat tindak lanjut...
            </div>
          </div>
        ) : pendingGroups.length === 0 ? (
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
          >
            <p className="text-sm text-gray-500">Tidak ada tindak lanjut yang menunggu persetujuan penyelesaian.</p>
          </motion.div>
        ) : (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="space-y-4"
          >
            {pendingGroups.map(([apId, items]) => {
              const ap = items[0].action_plan
              return (
                <div key={apId} className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                  <div className="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-200">
                    <div className="flex items-center gap-3 flex-wrap">
                      <Link
                        to={`/action-plan/${apId}`}
                        className="inline-flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg border border-primary-200 hover:border-primary-400 hover:bg-primary-50 transition-all font-semibold text-sm text-primary-700 hover:text-primary-800 shadow-sm"
                      >
                        <ExternalLink className="w-4 h-4" />
                        {ap?.code ?? `AP #${apId}`}
                      </Link>
                      <span className="text-sm text-gray-700">{ap?.title}</span>
                    </div>
                  </div>
                  <div className="p-4 space-y-3">
                    {items.map((fu) => (
                      <FollowUpRow key={fu.id} fu={fu} isManager={isManager} />
                    ))}
                  </div>
                </div>
              )
            })}
          </motion.div>
        )
      )}

      {tab === 'tindak-lanjut' && (
        isLoading ? (
          <div className="flex items-center justify-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div className="flex items-center gap-2 text-sm text-gray-500">
              <Spinner /> Memuat tindak lanjut...
            </div>
          </div>
        ) : groups.length === 0 ? (
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
          >
            <p className="text-sm text-gray-500">Belum ada tindak lanjut untuk departemen Anda.</p>
          </motion.div>
        ) : (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="space-y-4"
          >
            {groups.map(([apId, items]) => {
              const ap = items[0].action_plan
              return (
                <div key={apId} className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                  <div className="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-200">
                    <div className="flex items-center gap-3 flex-wrap">
                      <Link
                        to={`/action-plan/${apId}`}
                        className="inline-flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg border border-primary-200 hover:border-primary-400 hover:bg-primary-50 transition-all font-semibold text-sm text-primary-700 hover:text-primary-800 shadow-sm"
                      >
                        <ExternalLink className="w-4 h-4" />
                        {ap?.code ?? `AP #${apId}`}
                      </Link>
                      <span className="text-sm text-gray-700">{ap?.title}</span>
                    </div>
                  </div>
                  <div className="p-4 space-y-3">
                    {items.map((fu) => (
                      <FollowUpRow key={fu.id} fu={fu} isManager={isManager} />
                    ))}
                  </div>
                </div>
              )
            })}
          </motion.div>
        )
      )}
    </div>
  )
}