import { useState } from 'react'
import {
  usePendingVerifications,
  useRecordVerification,
  useFindingVerifications,
} from '@/hooks/useFindings'
import { DataTable, PageHeader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, StatusBadge, Select, Textarea, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { AuditorConclusion, Finding } from '@/types/finding'
import {
  AUDITOR_CONCLUSION_OPTIONS,
  getFindingStatusLabel,
} from '@/types/finding'
import { isAxiosError } from 'axios'

export default function VerificationsPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [verifying, setVerifying] = useState<Finding | null>(null)
  const [history, setHistory] = useState<Finding | null>(null)
  const [conclusion, setConclusion] = useState<AuditorConclusion>('ditutup')
  const [auditorResult, setAuditorResult] = useState('')
  const [verifiedDate, setVerifiedDate] = useState('')
  const [notes, setNotes] = useState('')

  const { data, isLoading, refetch } = usePendingVerifications({ page, per_page: 15 })
  const record = useRecordVerification()

  const { data: historyData } = useFindingVerifications(history?.id ?? 0)
  const selectedOption = AUDITOR_CONCLUSION_OPTIONS.find((o) => o.value === conclusion)
  const needsNotes = conclusion === 'perlu_perbaikan'

  const openVerify = (finding: Finding) => {
    setVerifying(finding)
    setConclusion('ditutup')
    setAuditorResult('')
    setVerifiedDate('')
    setNotes('')
  }

  const openHistory = (finding: Finding) => setHistory(finding)

  const submit = async () => {
    if (!verifying) return
    if (needsNotes && !notes.trim()) {
      showToast('Catatan wajib diisi saat mengembalikan temuan ke Manager IA', 'error')
      return
    }
    try {
      const result = await record.mutateAsync({
        id: verifying.id,
        auditor_conclusion: conclusion,
        auditor_result: auditorResult.trim() || null,
        verified_date: verifiedDate || null,
        notes: notes.trim() || null,
      })
      showToast(
        result.verification.is_closed
          ? 'Temuan berhasil ditutup'
          : 'Temuan dikembalikan ke Manager IA untuk ronde baru',
        'success',
      )
      setVerifying(null)
      refetch()
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan',
        'error',
      )
    }
  }

  const columns: Column<Finding>[] = [
    { key: 'code', header: 'Kode', body: (r) => r.code },
    { key: 'title', header: 'Judul', body: (r) => r.title },
    { key: 'round', header: 'Ronde', body: (r) => `#${r.current_round}` },
    {
      key: 'assessment',
      header: 'Assessment IA',
      body: (r) => r.assessment_status_label ?? '-',
    },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getFindingStatusLabel(r.status)} />,
    },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={() => openVerify(r)}>
            Catat Hasil
          </Button>
          <Button size="sm" variant="outline" onClick={() => openHistory(r)}>
            Riwayat
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Verifikasi"
        subtitle="Input hasil pemeriksaan auditor eksternal"
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
        emptyMessage="Tidak ada temuan yang menunggu verifikasi"
        permissionMenu="verifications"
        permissionAction="view"
      />

      <Modal
        open={!!verifying}
        onClose={() => setVerifying(null)}
        title={`Hasil Auditor: ${verifying?.code ?? ''}`}
      >
        <div className="space-y-3 max-h-[70vh] overflow-y-auto">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Kesimpulan Auditor</label>
            <Select
              value={conclusion}
              onChange={(e) => setConclusion(e.target.value as AuditorConclusion)}
            >
              {AUDITOR_CONCLUSION_OPTIONS.map((o) => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </Select>
            {selectedOption && (
              <p className="mt-1 text-xs text-gray-500">{selectedOption.description}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Hasil Pemeriksaan</label>
            <Textarea
              value={auditorResult}
              onChange={(e) => setAuditorResult(e.target.value)}
              placeholder="Ringkasan hasil pemeriksaan auditor eksternal..."
            />
          </div>

          <InputField
            label="Tanggal Verifikasi"
            type="date"
            value={verifiedDate}
            onChange={(e) => setVerifiedDate(e.target.value)}
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Catatan {needsNotes && <span className="text-red-500">*</span>}
            </label>
            <Textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder={
                needsNotes
                  ? 'Jelaskan temuan auditor yang perlu diperbaiki...'
                  : 'Catatan tambahan (opsional)...'
              }
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setVerifying(null)}>Batal</Button>
            <Button onClick={submit} disabled={record.isPending}>Simpan</Button>
          </div>
        </div>
      </Modal>

      <Modal
        open={!!history}
        onClose={() => setHistory(null)}
        title={`Riwayat Verifikasi: ${history?.code ?? ''}`}
      >
        <div className="space-y-2 max-h-[60vh] overflow-y-auto">
          {(historyData ?? []).length === 0 && (
            <p className="text-sm text-gray-500">Belum ada riwayat verifikasi.</p>
          )}
          {(historyData ?? []).map((v) => (
            <div key={v.id} className="border rounded p-3 text-sm">
              <div className="flex items-center justify-between">
                <StatusBadge status={v.auditor_conclusion_label ?? ''} />
                <span className="text-xs text-gray-500">
                  Ronde #{v.round} · {v.verified_date ?? v.created_at}
                </span>
              </div>
              {v.auditor_result && <p className="mt-2">{v.auditor_result}</p>}
              {v.notes && <p className="mt-1 text-xs text-gray-500">Catatan: {v.notes}</p>}
            </div>
          ))}
        </div>
      </Modal>
    </div>
  )
}