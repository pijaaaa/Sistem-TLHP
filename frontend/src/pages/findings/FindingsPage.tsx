import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ChevronRight, ChevronDown } from 'lucide-react'
import { useFindings, useDeleteFinding } from '@/hooks/useFindings'
import { useFindingTree } from '@/hooks/useReports'
import { useFollowUpsByActionPlan } from '@/hooks/useFollowUps'
import { useAuditeeDepartments } from '@/hooks/useLookups'
import { PageHeader, Can, FollowUpStatusBadge, ProgressBar, FollowUpDetailModal } from '@/components/shared'
import { Button, StatusBadge, Select, ConfirmDialog, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import {
  FINDING_STATUS_OPTIONS,
  SOURCE_OPTIONS,
  getFindingStatusVariant,
  getActionPlanStatusVariant,
  type Finding,
  type ActionPlan,
  type FollowUp,
} from '@/types/finding'
import { isAxiosError } from 'axios'

const FindingsPage = () => {
  const navigate = useNavigate()
  const { showToast } = useToast()
  const createPerm = usePermission('findings')
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const [fiscalYear, setFiscalYear] = useState('')
  const [source, setSource] = useState('')
  const [departmentId, setDepartmentId] = useState('')
  const [deleteItem, setDeleteItem] = useState<Finding | null>(null)
  const [detailFu, setDetailFu] = useState<FollowUp | null>(null)

  const { data: depts } = useAuditeeDepartments()
  const params = useMemo(
    () => ({ page, per_page: 15, status: status || undefined, fiscal_year: fiscalYear || undefined, source: source || undefined, department_id: departmentId || undefined, q: q || undefined }),
    [page, q, status, fiscalYear, source, departmentId],
  )
  const { data, isLoading } = useFindings(params)
  const remove = useDeleteFinding()

  const submitDelete = async () => {
    if (!deleteItem) return
    try {
      await remove.mutateAsync(deleteItem.id)
      showToast('Temuan berhasil dihapus.', 'success')
      setDeleteItem(null)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menghapus' : 'Gagal menghapus', 'error')
    }
  }

  return (
    <div className="space-y-4">
      <PageHeader
        title="Temuan"
        subtitle="Registrasi LHP auditor eksternal — perluas baris untuk melihat Action Plan & Tindak Lanjut"
        permissionMenu="findings"
        permissionAction="create"
        action={createPerm.create ? <Button onClick={() => navigate('/temuan/baru')}>+ Tambah Temuan</Button> : undefined}
      />

      <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
        <InputField placeholder="Cari judul / nomor / LHP..." value={q} onChange={(e) => { setQ(e.target.value); setPage(1) }} />
        <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }}>
          <option value="">Semua Status</option>
          {FINDING_STATUS_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
        <Select value={fiscalYear} onChange={(e) => { setFiscalYear(e.target.value); setPage(1) }}>
          <option value="">Semua Tahun</option>
          {Array.from({ length: 8 }, (_, i) => new Date().getFullYear() - i).map((y) => <option key={y} value={y}>{y}</option>)}
        </Select>
        <Select value={source} onChange={(e) => { setSource(e.target.value); setPage(1) }}>
          <option value="">Semua Sumber</option>
          {SOURCE_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
        <Select value={departmentId} onChange={(e) => { setDepartmentId(e.target.value); setPage(1) }}>
          <option value="">Semua Departemen</option>
          {(depts ?? []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
        </Select>
      </div>

      {isLoading ? (
        <p className="text-sm text-gray-500">Memuat...</p>
      ) : (data?.data ?? []).length === 0 ? (
        <p className="text-sm text-gray-500">Tidak ada temuan.</p>
      ) : (
        <div className="bg-white rounded-lg shadow divide-y divide-gray-100">
          {(data?.data ?? []).map((finding) => (
            <FindingRow key={finding.id} finding={finding} onOpenFu={setDetailFu} onDelete={setDeleteItem} />
          ))}
        </div>
      )}

      {(data?.total ?? 0) > 0 && (
        <div className="flex items-center justify-between text-sm text-gray-500">
          <span>Total {data?.total} temuan</span>
          <div className="flex gap-2">
            <Button size="sm" variant="outline" disabled={(data?.current_page ?? 1) <= 1} onClick={() => setPage((p) => p - 1)}>‹ Prev</Button>
            <span>Halaman {data?.current_page ?? 1}</span>
            <Button size="sm" variant="outline" disabled={(data?.current_page ?? 1) >= (data?.last_page ?? 1)} onClick={() => setPage((p) => p + 1)}>Next ›</Button>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={!!deleteItem}
        title="Hapus Temuan?"
        message={`Yakin hapus "${deleteItem?.title}"?`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />

      <FollowUpDetailModal followUp={detailFu} onClose={() => setDetailFu(null)} />
    </div>
  )
}

const FindingRow = ({ finding, onOpenFu, onDelete }: { finding: Finding; onOpenFu: (fu: FollowUp) => void; onDelete: (f: Finding) => void }) => {
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()

  return (
    <div>
      <div className="flex items-center gap-2 px-4 py-3 flex-wrap">
        <button onClick={() => setOpen((o) => !o)} className="text-gray-500 hover:text-gray-700">{open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}</button>
        <button className="text-blue-600 hover:underline font-medium" onClick={() => navigate(`/temuan/${finding.id}`)}>
          {finding.registration_number ?? `Draft #${finding.id}`}
        </button>
        <span className="text-sm text-gray-700 truncate flex-1">{finding.title}</span>
        <StatusBadge status={finding.status_label} variant={getFindingStatusVariant(finding.status)} />
        <span className="text-sm text-gray-500">Umur {finding.age_days} hr</span>
        <Can menu="findings" action="delete">
          {finding.status === 'DRAFT' && <Button size="sm" variant="destructive" onClick={() => onDelete(finding)}>Hapus</Button>}
        </Can>
      </div>
      {open && (
        <div className="border-l border-gray-200 ml-8">
          <ApChildren findingId={finding.id} onOpenFu={onOpenFu} />
        </div>
      )}
    </div>
  )
}

const ApChildren = ({ findingId, onOpenFu }: { findingId: number; onOpenFu: (fu: FollowUp) => void }) => {
  const { data, isLoading } = useFindingTree(findingId)

  if (isLoading) return <p className="text-sm text-gray-400 px-4 py-2">Memuat action plan...</p>
  if (!data || data.length === 0) return <p className="text-sm text-gray-400 px-4 py-2">Belum ada action plan.</p>

  return (
    <div>
      {data.map((ap) => (
        <ApRow key={ap.id} ap={ap} onOpenFu={onOpenFu} />
      ))}
    </div>
  )
}

const ApRow = ({ ap, onOpenFu }: { ap: ActionPlan; onOpenFu: (fu: FollowUp) => void }) => {
  const [open, setOpen] = useState(false)
  const { data: followUps, isLoading } = useFollowUpsByActionPlan(open ? ap.id : 0)
  const navigate = useNavigate()

  return (
    <div>
      <div className="flex items-center gap-2 px-4 py-2 flex-wrap bg-gray-50/70">
        <button onClick={() => setOpen((o) => !o)} className="text-gray-500 hover:text-gray-700">{open ? <ChevronDown size={14} /> : <ChevronRight size={14} />}</button>
        <button className="text-blue-600 hover:underline text-sm font-medium" onClick={() => navigate(`/action-plan/${ap.id}`)}>{ap.code}</button>
        <span className="text-sm text-gray-700 truncate flex-1">{ap.title}</span>
        <span className="text-sm text-gray-500">{ap.department?.name ?? '-'}</span>
        <StatusBadge status={ap.status_label} variant={getActionPlanStatusVariant(ap.status)} />
        <div className="w-28"><ProgressBar value={ap.progress} /></div>
      </div>
      {open && (
        <div className="border-l border-gray-200 ml-8">
          {isLoading ? (
            <p className="text-sm text-gray-400 px-4 py-2">Memuat tindak lanjut...</p>
          ) : (followUps?.data ?? []).length === 0 ? (
            <p className="text-sm text-gray-400 px-4 py-2">Belum ada tindak lanjut.</p>
          ) : (
            (followUps?.data ?? []).map((fu) => (
              <div key={fu.id} className="flex items-center gap-2 px-4 py-2 flex-wrap hover:bg-gray-100 cursor-pointer" onClick={() => onOpenFu(fu)}>
                <span className="text-sm text-gray-800 flex-1">{fu.description}</span>
                <span className="text-sm text-gray-500">Bobot {fu.weight}</span>
                <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
                <span className="text-sm text-gray-500">Target {fu.target_date}</span>
              </div>
            ))
          )}
        </div>
      )}
    </div>
  )
}

export default FindingsPage