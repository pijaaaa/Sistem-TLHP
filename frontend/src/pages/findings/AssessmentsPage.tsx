import { useState } from 'react'
import { usePendingAssessments, useAssessFinding } from '@/hooks/useFindings'
import { useDepartments } from '@/hooks/useDepartments'
import { DataTable, PageHeader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, StatusBadge, Select, Textarea, Checkbox } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { AssessmentStatus, Finding } from '@/types/finding'
import { ASSESSMENT_OPTIONS, getFindingStatusLabel } from '@/types/finding'
import { isAxiosError } from 'axios'

export default function AssessmentsPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [assessing, setAssessing] = useState<Finding | null>(null)
  const [status, setStatus] = useState<AssessmentStatus>('ssr')
  const [note, setNote] = useState('')
  const [deptIds, setDeptIds] = useState<number[]>([])

  const { data, isLoading, refetch } = usePendingAssessments({ page, per_page: 15 })
  const { data: deptData } = useDepartments({ per_page: 100 })
  const assess = useAssessFinding()

  const departments = deptData?.data ?? []
  const selectedOption = ASSESSMENT_OPTIONS.find((o) => o.value === status)
  const needsReason = selectedOption?.requiresReason ?? false
  const isBsr = status === 'bsr'

  const openAssess = (finding: Finding) => {
    setAssessing(finding)
    setStatus('ssr')
    setNote('')
    setDeptIds([])
  }

  const submit = async () => {
    if (!assessing) return
    if (needsReason && !note.trim()) {
      showToast('Alasan wajib diisi untuk status Tidak Dapat Ditindaklanjuti', 'error')
      return
    }
    try {
      await assess.mutateAsync({
        id: assessing.id,
        assessment_status: status,
        note: note.trim() || null,
        department_ids: isBsr && deptIds.length > 0 ? deptIds : undefined,
      })
      showToast('Assessment berhasil disimpan', 'success')
      setAssessing(null)
      refetch()
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan assessment' : 'Gagal menyimpan assessment',
        'error',
      )
    }
  }

  const columns: Column<Finding>[] = [
    { key: 'code', header: 'Kode', body: (r) => r.code },
    { key: 'title', header: 'Judul', body: (r) => r.title },
    { key: 'round', header: 'Ronde', body: (r) => `#${r.current_round}` },
    { key: 'progress', header: 'Progress', body: (r) => `${Number(r.progress ?? 0).toFixed(2)}%` },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getFindingStatusLabel(r.status)} />,
    },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <Button size="sm" variant="outline" onClick={() => openAssess(r)}>
          Assess
        </Button>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Assessment"
        subtitle="Tetapkan status temuan setelah semua departemen meneruskan"
      />

      <DataTable
        data={data?.data ?? []}
        columns={columns}
        loading={isLoading}
        pagination={data?.meta ? {
          current: data.meta.current_page,
          perPage: data.meta.per_page,
          total: data.meta.total,
          onChange: setPage,
        } : undefined}
        emptyMessage="Tidak ada temuan yang menunggu assessment"
        permissionMenu="assessments"
        permissionAction="view"
      />

      <Modal
        open={!!assessing}
        onClose={() => setAssessing(null)}
        title={`Assessment: ${assessing?.code ?? ''}`}
      >
        <div className="space-y-3 max-h-[70vh] overflow-y-auto">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Status Assessment</label>
            <Select value={status} onChange={(e) => setStatus(e.target.value as AssessmentStatus)}>
              {ASSESSMENT_OPTIONS.map((o) => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </Select>
            {selectedOption && (
              <p className="mt-1 text-xs text-gray-500">{selectedOption.description}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Catatan {needsReason && <span className="text-red-500">*</span>}
            </label>
            <Textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder={
                needsReason
                  ? 'Jelaskan alasan tidak dapat ditindaklanjuti...'
                  : 'Catatan assessment (opsional)...'
              }
            />
          </div>

          {isBsr && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Departemen Ronde Baru
              </label>
              <p className="text-xs text-gray-500 mb-2">
                Kosongkan untuk membawa seluruh departemen dari ronde sebelumnya.
              </p>
              <div className="space-y-2 max-h-40 overflow-y-auto border rounded p-2">
                {departments.map((d) => (
                  <label key={d.id} className="flex items-center gap-2 text-sm">
                    <Checkbox
                      checked={deptIds.includes(d.id)}
                      onChange={(e) => {
                        setDeptIds(
                          e.target.checked ? [...deptIds, d.id] : deptIds.filter((x) => x !== d.id),
                        )
                      }}
                    />
                    {d.name} ({d.code})
                  </label>
                ))}
              </div>
            </div>
          )}

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setAssessing(null)}>Batal</Button>
            <Button onClick={submit} disabled={assess.isPending}>Simpan</Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}