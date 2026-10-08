import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useFollowUps, useFollowUpComments } from '@/hooks/useFollowUps'
import { PageHeader, ReviewActionBar, ReviewTimeline, FollowUpStatusBadge, CommentThread, Tabs } from '@/components/shared'
import { Button } from '@/components/ui'
import { useAuth } from '@/contexts/AuthContext'
import type { FollowUp } from '@/types/finding'

const FollowUpRow = ({ fu, isManager }: { fu: FollowUp; isManager: boolean }) => {
  const [showTimeline, setShowTimeline] = useState(false)
  const { data: comments } = useFollowUpComments(fu.id)
  const hasIaComment = (comments ?? []).some((c) => c.kind === 'IA_COMMENT')

  return (
    <div className="border rounded p-3 space-y-2">
      <div className="flex items-start justify-between gap-3 flex-wrap">
        <div className="min-w-0">
          <p className="text-sm text-gray-800">{fu.description}</p>
          <p className="text-xs text-gray-500">
            Target {fu.target_date} · Bobot {fu.weight} ·{(fu.assignees ?? []).map((u) => u.name).join(', ') || '-'}
          </p>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
          {hasIaComment && <span className="text-xs text-blue-600">Komentar IA</span>}
        </div>
      </div>

      {isManager && (
        <ReviewActionBar
          followUp={fu}
          canDecide={fu.status === 'DIAJUKAN' || fu.status === 'MENUNGGU_PERSETUJUAN_SELESAI'}
          canReturn={fu.status === 'DISETUJUI'}
        />
      )}

      <div className="flex gap-2">
        <Button size="sm" variant="ghost" onClick={() => setShowTimeline(!showTimeline)}>
          {showTimeline ? 'Tutup Riwayat' : 'Riwayat Keputusan'}
        </Button>
      </div>

      {showTimeline && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <ReviewTimeline followUpId={fu.id} />
          <CommentThread followUpId={fu.id} />
        </div>
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

  const isManager = user?.role === 'manager_dept'

  return (
    <div className="space-y-4">
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
          <p className="text-sm text-gray-500">Memuat...</p>
        ) : pendingGroups.length === 0 ? (
          <p className="text-sm text-gray-500">Tidak ada tindak lanjut yang menunggu persetujuan penyelesaian.</p>
        ) : (
          pendingGroups.map(([apId, items]) => {
            const ap = items[0].action_plan
            return (
              <div key={apId} className="bg-white rounded-lg shadow p-4 space-y-3">
                <Link className="text-blue-600 hover:underline font-medium" to={`/action-plan/${apId}`}>
                  {ap?.code ?? `AP #${apId}`}
                </Link>
                <span className="text-sm text-gray-500 ml-2">{ap?.title}</span>
                <div className="space-y-3">
                  {items.map((fu) => (
                    <FollowUpRow key={fu.id} fu={fu} isManager={isManager} />
                  ))}
                </div>
              </div>
            )
          })
        )
      )}

      {tab === 'tindak-lanjut' && (
        isLoading ? (
          <p className="text-sm text-gray-500">Memuat...</p>
        ) : groups.length === 0 ? (
          <p className="text-sm text-gray-500">Belum ada tindak lanjut untuk departemen Anda.</p>
        ) : (
          groups.map(([apId, items]) => {
            const ap = items[0].action_plan
            return (
              <div key={apId} className="bg-white rounded-lg shadow p-4 space-y-3">
                <div className="flex items-center justify-between flex-wrap gap-2">
                  <div>
                    <Link className="text-blue-600 hover:underline font-medium" to={`/action-plan/${apId}`}>
                      {ap?.code ?? `AP #${apId}`}
                    </Link>
                    <span className="text-sm text-gray-500 ml-2">{ap?.title}</span>
                  </div>
                </div>
                <div className="space-y-3">
                  {items.map((fu) => (
                    <FollowUpRow key={fu.id} fu={fu} isManager={isManager} />
                  ))}
                </div>
              </div>
            )
          })
        )
      )}
    </div>
  )
}