import { useFollowUpReviews } from '@/hooks/useFollowUps'
import { Spinner } from '@/components/ui'

const ReviewTimeline = ({ followUpId }: { followUpId: number }) => {
  const { data, isLoading } = useFollowUpReviews(followUpId)

  if (isLoading) return <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat riwayat...</div>

  if (!data || data.length === 0) return <p className="text-sm text-gray-500">Belum ada keputusan.</p>

  return (
    <ul className="space-y-2">
      {data.map((r) => (
        <li key={r.id} className="text-sm border-l-2 border-gray-200 pl-3">
          <div className="flex items-center gap-2">
            <span className="font-medium">{r.decision_label}</span>
            <span className="text-xs text-gray-400">
              {r.reviewer?.name} · {new Date(r.created_at).toLocaleString('id-ID')}
            </span>
          </div>
          {r.note && <p className="text-gray-600 mt-0.5">{r.note}</p>}
          {(r.decision === 'OVERRIDE_BOBOT' || r.weight_before !== r.weight_after) && (
            <p className="text-xs text-gray-500">Bobot {r.weight_before} → {r.weight_after}</p>
          )}
        </li>
      ))}
    </ul>
  )
}

export { ReviewTimeline }