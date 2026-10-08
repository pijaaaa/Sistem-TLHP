import { useExternalStatusRecords } from '@/hooks/useExternalStatus'
import { externalStatusApi } from '@/api/findings'
import { confirmDownload } from '@/lib/utils'
import { Button, Spinner } from '@/components/ui'

const ExternalStatusTimeline = ({ findingId }: { findingId: number }) => {
  const { data, isLoading } = useExternalStatusRecords(findingId)

  if (isLoading) return <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>

  if (!data || data.length === 0) return <p className="text-sm text-gray-500">Belum ada catatan status eksternal.</p>

  return (
    <ul className="space-y-4">
      {data.map((r) => (
        <li key={r.id} className="border-l-2 border-blue-200 pl-4">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm font-medium">{r.status_label}</span>
            <span className="text-xs text-gray-500">
              {r.recorder?.name} · {r.recorded_at ? new Date(r.recorded_at).toLocaleString('id-ID') : '-'}
            </span>
          </div>
          {r.note && <p className="text-sm text-gray-700 mt-1">{r.note}</p>}
          {r.action_plans.length > 0 && (
            <p className="text-xs text-gray-500 mt-1">AP diperbaiki: {r.action_plans.map((a) => a.code).join(', ')}</p>
          )}
          <div className="flex gap-2 mt-2 flex-wrap">
            {r.documents.map((d) => (
              <Button key={d.id} size="sm" variant="outline" onClick={() => confirmDownload(externalStatusApi.recordDownload(findingId, r.id, d.id))}>
                {d.name}
              </Button>
            ))}
          </div>
        </li>
      ))}
    </ul>
  )
}

export { ExternalStatusTimeline }