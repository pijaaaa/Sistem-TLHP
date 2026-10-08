import { useNavigate, useParams } from 'react-router-dom'
import { useFinding } from '@/hooks/useFindings'
import { PageHeader, ExternalStatusTimeline, ExternalStatusForm } from '@/components/shared'
import { Spinner, StatusBadge } from '@/components/ui'
import { getFindingStatusVariant } from '@/types/finding'

export default function ExternalStatusDetailPage() {
  const { findingId } = useParams<{ findingId: string }>()
  const id = Number(findingId)
  const navigate = useNavigate()
  const { data: finding, isLoading } = useFinding(id)

  if (isLoading) return <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
  if (!finding) return null

  return (
    <div className="space-y-4 max-w-3xl">
      <PageHeader
        title={finding.registration_number ?? `#${finding.id}`}
        subtitle={finding.title}
        action={<StatusBadge status={finding.status_label} variant={getFindingStatusVariant(finding.status)} />}
      />

      <div className="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <span className="text-sm text-gray-500">Departemen Auditee: <b>{finding.auditee_departments?.map((d) => d.name).join(', ') || '-'}</b></span>
        <span className="text-sm text-gray-500">Deadline dan AP Sesuai siap direview</span>
      </div>

      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-sm font-semibold mb-4">Catat Status Baru</h3>
        <ExternalStatusForm
          findingId={id}
          findingLabel={`${finding.registration_number ?? `#${finding.id}`} — ${finding.title}`}
          onDone={() => navigate('/status-eksternal')}
        />
      </div>

      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-sm font-semibold mb-4">Riwayat Status Eksternal</h3>
        <ExternalStatusTimeline findingId={id} />
      </div>
    </div>
  )
}