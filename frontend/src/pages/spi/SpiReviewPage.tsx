import { useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useSpiBundle, useSpiAssess, useSpiComplete } from '@/hooks/useSpiReview'
import { PageHeader, RevisionTabs, ProgressBar, FollowUpStatusBadge } from '@/components/shared'
import { Button, Spinner, StatusBadge, Input, Textarea, FormField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { confirmDownload } from '@/lib/utils'
import { followUpsApi } from '@/api/findings'
import { getActionPlanStatusVariant, type SpiBundleFollowUp } from '@/types/finding'
import { isAxiosError } from 'axios'

type Assessment = { result: 'SESUAI' | 'REVISI'; note: string }

export default function SpiReviewPage() {
  const { actionPlanId } = useParams<{ actionPlanId: string }>()
  const id = Number(actionPlanId)
  const navigate = useNavigate()
  const { showToast } = useToast()

  const { data: bundle, isLoading } = useSpiBundle(id)
  const assess = useSpiAssess()
  const complete = useSpiComplete()

  const currentRevision = bundle?.action_plan.current_revision ?? 0
  const [activeRevision, setActiveRevision] = useState<number>(currentRevision)
  const [assessments, setAssessments] = useState<Record<number, Assessment>>({})
  const [newDeadline, setNewDeadline] = useState('')

  const activeItems = useMemo(() => bundle?.follow_ups[activeRevision] ?? [], [bundle, activeRevision])
  const interactive = activeRevision === currentRevision && bundle?.action_plan.status === 'DIAJUKAN_KE_SPI'

  if (isLoading) {
    return <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
  }

  if (!bundle) return null

  const ap = bundle.action_plan

  const setAssessment = (fuId: number, patch: Partial<Assessment>) =>
    setAssessments((prev) => ({ ...prev, [fuId]: { result: 'SESUAI', note: '', ...prev[fuId], ...patch } }))

  const chosen = (fu: SpiBundleFollowUp): Assessment =>
    assessments[fu.id] ?? (fu.spi_item ? { result: fu.spi_item.result, note: fu.spi_item.note ?? '' } : { result: 'SESUAI', note: '' })

  const missingAssessment = activeItems
    .map((fu) => chosen(fu))
    .some((a) => (a.result === 'REVISI' && !a.note.trim()))

  const doAssess = async (alsoComplete: boolean) => {
    try {
      const items = activeItems.map((fu) => ({
        follow_up_id: fu.id,
        result: chosen(fu).result,
        note: chosen(fu).note || null,
      }))

      if (alsoComplete) {
        const apiItems = items.map((i) => chooseNote(i))
        await assess.mutateAsync({ id, items: apiItems })
        await complete.mutateAsync({ id, newDeadline: newDeadline || null })
        showToast('Review SPI selesai.', 'success')
        navigate('/review-spi')
      } else {
        await assess.mutateAsync({ id, items })
        showToast('Penilaian disimpan.', 'success')
      }
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    }
  }

  const chooseNote = (i: { follow_up_id: number; result: string; note: string | null }) =>
    i.result === 'SESUAI' ? { ...i, note: null } : i

  return (
    <div className="space-y-4">
      <PageHeader
        title={ap.code}
        subtitle={ap.title}
        action={
          <div className="flex items-center gap-2">
            <StatusBadge status={ap.status_label} variant={getActionPlanStatusVariant(ap.status)} />
          </div>
        }
      />

      <div className="bg-white rounded-lg shadow p-4 flex items-center gap-6 flex-wrap text-sm">
        <span>Temuan: <b>{ap.finding?.registration_number ?? '-'}</b></span>
        <span>Departemen: <b>{ap.department?.name ?? '-'}</b></span>
        <span>Deadline: <b>{ap.deadline ?? '-'}</b></span>
        <span>Revisi: <b>{ap.revision_label}</b></span>
      </div>

      <RevisionTabs revisions={bundle.revisions} active={activeRevision} onChange={setActiveRevision} />

      <div className="space-y-3">
        {(bundle.revisions ?? []).filter((r) => r.revision_no === activeRevision && r.revision_no > 0).map((r) => (
          <div key={r.revision_no} className="bg-yellow-50 border border-yellow-200 rounded p-3 text-sm">
            <b>Revisi {r.revision_no}</b> · {r.source_label} · diminta {r.requested_at ? new Date(r.requested_at).toLocaleString('id-ID') : '-'} oleh {r.requested_by ?? '-'}
            <p className="mt-1 text-gray-700">{r.reason}</p>
            {r.new_deadline && <p className="text-xs text-gray-600">Deadline baru: {r.new_deadline}</p>}
          </div>
        ))}
      </div>

      <div className="space-y-3">
        {activeItems.length === 0 ? (
          <p className="text-sm text-gray-500">Tidak ada tindak lanjut pada revisi ini.</p>
        ) : (
          activeItems.map((fu) => {
            const a = chosen(fu)
            return (
              <div key={fu.id} className="bg-white rounded-lg shadow p-4 space-y-2">
                <div className="flex items-start justify-between gap-3 flex-wrap">
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-gray-800">{fu.description}</p>
                    <p className="text-xs text-gray-500">
                      Target {fu.target_date} · Bobot {fu.weight} · {(fu.assignees ?? []).map((x) => x.name).join(', ') || '-'}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
                    <span className="text-xs text-gray-400">Progres {fu.progress}%</span>
                  </div>
                </div>

                <ProgressBar value={fu.progress} />

                {fu.progress_reports.map((r) => (
                  <div key={r.id} className="text-sm text-gray-600 bg-gray-50 rounded p-2">
                    <b>{r.progress_value}%</b> · {r.reported_at ? new Date(r.reported_at).toLocaleString('id-ID') : ''}
                    {r.note && <span> — {r.note}</span>}
                    <div className="flex gap-2 mt-1">
                      {r.documents.map((d) => (
                        <button
                          key={d.id}
                          className="text-blue-600 hover:underline text-xs"
                          onClick={() => confirmDownload(followUpsApi.progressReportDownload(fu.id, r.id, d.id))}
                        >
                          {d.name}
                        </button>
                      ))}
                    </div>
                  </div>
                ))}

                {fu.reviews.length > 0 && (
                  <div className="text-xs text-gray-500">
                    Keputusan: {fu.reviews.map((r) => `${r.decision_label}${r.note ? ' (' + r.note + ')' : ''}`).join('; ')}
                  </div>
                )}

                {fu.comments.length > 0 && (
                  <div className="text-xs text-gray-500">
                    Komentar: {fu.comments.map((c) => `[${c.kind_label}] ${c.author}: ${c.body}`).join('; ')}
                  </div>
                )}

                {interactive && (
                  <div className="border-t pt-3">
                    <div className="flex items-center gap-4">
                      {(['SESUAI', 'REVISI'] as const).map((r) => (
                        <label key={r} className="flex items-center gap-1 text-sm">
                          <input
                            type="radio"
                            checked={a.result === r}
                            onChange={() => setAssessment(fu.id, { result: r })}
                          />
                          {r === 'SESUAI' ? 'Sesuai' : 'Revisi'}
                        </label>
                      ))}
                      {a.result === 'REVISI' && (
                        <span className="text-xs text-gray-500">(catatan wajib)</span>
                      )}
                    </div>
                    {a.result === 'REVISI' && (
                      <div className="mt-2">
                        <Textarea
                          rows={2}
                          placeholder="Catatan revisi *"
                          value={a.note}
                          onChange={(e) => setAssessment(fu.id, { note: e.target.value })}
                        />
                      </div>
                    )}
                  </div>
                )}
              </div>
            )
          })
        )}
      </div>

      {interactive && (
        <div className="bg-white rounded-lg shadow p-4 space-y-3">
          {activeItems.some((fu) => chosen(fu).result === 'REVISI') && (
            <FormField label="Deadline Baru (opsional)">
              <Input type="date" value={newDeadline} onChange={(e) => setNewDeadline(e.target.value)} className="max-w-xs" />
            </FormField>
          )}
          {missingAssessment && (
            <p className="text-xs text-red-600">Catatan revisi wajib diisi untuk semua TL berstatus Revisi.</p>
          )}
          <div className="flex justify-end gap-3">
            <Button variant="outline" onClick={() => doAssess(false)} disabled={assess.isPending}>Simpan Penilaian</Button>
            <Button onClick={() => doAssess(true)} disabled={assess.isPending || complete.isPending || missingAssessment}>
              {complete.isPending ? 'Menyelesaikan...' : 'Simpan & Selesaikan'}
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}