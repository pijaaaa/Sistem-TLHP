import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useFollowUps, useSubmitFollowUps } from '@/hooks/useFollowUps'
import { PageHeader, Can, FollowUpStatusBadge, ProgressBar, FollowUpDetailModal } from '@/components/shared'
import { Button, Select } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { FOLLOW_UP_STATUS_OPTIONS, type FollowUp } from '@/types/finding'
import { isAxiosError } from 'axios'

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
    <div className="space-y-4">
      <PageHeader title="Tindak Lanjut" subtitle="Kegiatan yang disusun PIC untuk menjalankan action plan" />

      <div className="flex items-center gap-3">
        <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-64">
          <option value="">Semua Status</option>
          {FOLLOW_UP_STATUS_OPTIONS.map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
      </div>

      {isLoading ? (
        <p className="text-sm text-gray-500">Memuat...</p>
      ) : groups.length === 0 ? (
        <p className="text-sm text-gray-500">Belum ada tindak lanjut.</p>
      ) : (
        groups.map(([apId, items]) => {
          const ap = items[0].action_plan
          return (
            <div key={apId} className="bg-white rounded-lg shadow overflow-hidden">
              <div className="px-4 py-3 bg-gray-50 border-b flex items-center justify-between flex-wrap gap-2">
                <div>
                  <Link className="text-blue-600 hover:underline font-medium" to={`/action-plan/${apId}`}>
                    {ap?.code ?? `AP #${apId}`}
                  </Link>
                  <span className="text-sm text-gray-500 ml-2">{ap?.title}</span>
                </div>
                <Can menu="follow_ups" action="create">
                  {ap?.status === 'PROSES_TINDAK_LANJUT' && (
                    <Link
                      to={`/action-plan/${apId}/tindak-lanjut/susun`}
                      className="inline-flex items-center justify-center h-8 px-3 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700"
                    >
                      Susun Tindak Lanjut
                    </Link>
                  )}
                </Can>
              </div>
              <table className="w-full">
                <thead>
                  <tr className="bg-gray-50">
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Uraian</th>
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Target</th>
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Bobot</th>
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Progres</th>
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Status</th>
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">PIC</th>
                    <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((fu) => (
                    <tr key={fu.id} className="border-t">
                      <td className="px-4 py-2 text-sm">{fu.description}</td>
                      <td className="px-4 py-2 text-sm whitespace-nowrap">{fu.target_date}</td>
                      <td className="px-4 py-2 text-sm">{fu.weight}</td>
                      <td className="px-4 py-2 w-36"><ProgressBar value={fu.progress} /></td>
                      <td className="px-4 py-2"><FollowUpStatusBadge status={fu.status} label={fu.status_label} /></td>
                      <td className="px-4 py-2 text-sm">{(fu.assignees ?? []).map((u) => u.name).join(', ') || '-'}</td>
                      <td className="px-4 py-2">
                        <div className="flex gap-2">
                          <Button size="sm" variant="outline" onClick={() => setDetailFu(fu)}>Detail / Progres</Button>
                          {(fu.status === 'DRAFT' || fu.status === 'REVISI') && (
                            <Button size="sm" variant="secondary" onClick={() => doSubmit(fu)}>Ajukan</Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )
        })
      )}

      <FollowUpDetailModal followUp={detailFu} onClose={() => setDetailFu(null)} />
    </div>
  )
}