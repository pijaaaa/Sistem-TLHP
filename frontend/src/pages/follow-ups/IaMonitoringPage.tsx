import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useFollowUps } from '@/hooks/useFollowUps'
import { usePermission } from '@/hooks/usePermission'
import { PageHeader, CommentThread, FollowUpStatusBadge } from '@/components/shared'
import { Select } from '@/components/ui'
import { FOLLOW_UP_STATUS_OPTIONS } from '@/types/finding'

export default function IaMonitoringPage() {
  const [status, setStatus] = useState('')
  const canComment = usePermission('ia_monitoring').create

  const params = useMemo(() => ({ per_page: 100, status: status || undefined }), [status])
  const { data, isLoading } = useFollowUps(params)

  return (
    <div className="space-y-4">
      <PageHeader
        title="Pemantauan Internal Audit"
        subtitle="Tindak lanjut yang sudah disetujui manager — tanpa komentar berarti setuju"
      />

      <div className="flex items-center gap-3">
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-64">
          <option value="">Semua Status (Disetujui ke atas)</option>
          {FOLLOW_UP_STATUS_OPTIONS.filter((s) => ['DISETUJUI', 'MENUNGGU_PERSETUJUAN_SELESAI', 'SELESAI'].includes(s.value)).map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
      </div>

      {isLoading ? (
        <p className="text-sm text-gray-500">Memuat...</p>
      ) : !data || data.data.length === 0 ? (
        <p className="text-sm text-gray-500">Belum ada tindak lanjut yang disetujui.</p>
      ) : (
        <div className="space-y-3">
          {data.data.map((fu) => {
            const ap = fu.action_plan
            return (
              <div key={fu.id} className="bg-white rounded-lg shadow p-4 space-y-3">
                <div className="flex items-start justify-between gap-3 flex-wrap">
                  <div className="min-w-0">
                    <p className="text-sm text-gray-800">{fu.description}</p>
                    <p className="text-xs text-gray-500">
                      <Link className="text-blue-600 hover:underline" to={`/action-plan/${fu.action_plan_id}`}>
                        {ap?.code ?? `AP #${fu.action_plan_id}`}
                      </Link>{' '}
                      · Target {fu.target_date} · Bobot {fu.weight} · Progres {fu.progress}% ·{' '}
                      {ap?.department?.name ?? '-'}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
                    <span className="text-xs text-gray-400">{(fu.assignees ?? []).map((u) => u.name).join(', ')}</span>
                  </div>
                </div>
                <div className="lg:max-w-xl">
                  <CommentThread followUpId={fu.id} canComment={canComment} placeholder="Komentar tanpa penghakiman = setuju..." />
                </div>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}