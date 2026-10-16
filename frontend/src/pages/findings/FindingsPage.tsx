import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ChevronRight, ChevronDown, Plus, FileText } from 'lucide-react'
import { useFindings, useDeleteFinding } from '@/hooks/useFindings'
import { useFindingTree } from '@/hooks/useReports'
import { useFollowUpsByActionPlan } from '@/hooks/useFollowUps'
import { useAuditeeDepartments, useFindingSources } from '@/hooks/useLookups'
import { 
  PageHeader, 
  Can, 
  FollowUpStatusBadge, 
  ProgressBar, 
  FollowUpDetailModal, 
  SearchBar, 
  FilterChip,
  EmptyState 
} from '@/components/shared'
import { Button, StatusBadge, Select, ConfirmDialog, Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import {
  FINDING_STATUS_OPTIONS,
  getFindingStatusVariant,
  getActionPlanStatusVariant,
  type Finding,
  type ActionPlan,
  type FollowUp,
} from '@/types/finding'
import { isAxiosError } from 'axios'
import { motion } from 'framer-motion'

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
  const { data: sources } = useFindingSources()
  const params = useMemo(
    () => ({ page, per_page: 15, status: status || undefined, fiscal_year: fiscalYear || undefined, source: source || undefined, department_id: departmentId || undefined, q: q || undefined }),
    [page, q, status, fiscalYear, source, departmentId],
  )
  const { data, isLoading } = useFindings(params)
  const remove = useDeleteFinding()

  const activeFilters = [
    { key: 'status', value: status, label: FINDING_STATUS_OPTIONS.find(s => s.value === status)?.label },
    { key: 'fiscalYear', value: fiscalYear, label: `Tahun ${fiscalYear}` },
    { key: 'source', value: source, label: sources?.find(s => s.code === source)?.name },
    { key: 'departmentId', value: departmentId, label: depts?.find(d => d.id === Number(departmentId))?.name },
  ].filter(f => f.value)

  const clearFilter = (key: string) => {
    if (key === 'status') setStatus('')
    if (key === 'fiscalYear') setFiscalYear('')
    if (key === 'source') setSource('')
    if (key === 'departmentId') setDepartmentId('')
    setPage(1)
  }

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
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Temuan</h1>
          <p className="text-sm text-gray-600 mt-1">Registrasi LHP auditor eksternal</p>
        </div>
        {createPerm.create && (
          <Button onClick={() => navigate('/temuan/baru')} className="gap-2">
            <Plus size={18} />
            Tambah Temuan
          </Button>
        )}
      </div>

      {/* Search & Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4">
        <SearchBar
          value={q}
          onChange={(v) => { setQ(v); setPage(1) }}
          placeholder="Cari judul, nomor registrasi, atau nomor LHP..."
          className="max-w-2xl"
        />
        
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
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
            {(sources ?? []).map((s) => <option key={s.code} value={s.code}>{s.name}</option>)}
          </Select>
          <Select value={departmentId} onChange={(e) => { setDepartmentId(e.target.value); setPage(1) }}>
            <option value="">Semua Departemen</option>
            {(depts ?? []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
          </Select>
        </div>

        {/* Active Filters */}
        {activeFilters.length > 0 && (
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm text-gray-600">Filter aktif:</span>
            {activeFilters.map((f) => (
              <FilterChip
                key={f.key}
                label={f.label!}
                onRemove={() => clearFilter(f.key)}
              />
            ))}
          </div>
        )}
      </div>

      {/* Results */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Spinner /> Memuat data temuan...
          </div>
        </div>
      ) : (data?.data ?? []).length === 0 ? (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
        >
          <EmptyState
            icon={FileText}
            title="Tidak ada temuan"
            description={q || activeFilters.length > 0 ? "Tidak ada temuan yang sesuai dengan pencarian atau filter." : "Belum ada temuan yang terdaftar."}
            action={createPerm.create ? {
              label: "Tambah Temuan",
              onClick: () => navigate('/temuan/baru')
            } : undefined}
          />
        </motion.div>
      ) : (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100"
        >
          {(data?.data ?? []).map((finding) => (
            <FindingRow key={finding.id} finding={finding} onOpenFu={setDetailFu} onDelete={setDeleteItem} />
          ))}
        </motion.div>
      )}

      {/* Pagination */}
      {(data?.total ?? 0) > 0 && (
        <div className="flex items-center justify-between flex-wrap gap-4 text-sm text-gray-600 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <span>
            Menampilkan <strong>{((data?.current_page ?? 1) - 1) * (data?.per_page ?? 15) + 1}</strong> - <strong>{Math.min((data?.current_page ?? 1) * (data?.per_page ?? 15), data?.total ?? 0)}</strong> dari <strong>{data?.total}</strong> temuan
          </span>
          <div className="flex items-center gap-2">
            <Button 
              size="sm" 
              variant="outline" 
              disabled={(data?.current_page ?? 1) <= 1} 
              onClick={() => setPage((p) => p - 1)}
            >
              ‹ Sebelumnya
            </Button>
            <span className="px-3 py-1 bg-gray-50 rounded border border-gray-200">
              Hal. {data?.current_page ?? 1} / {data?.last_page ?? 1}
            </span>
            <Button 
              size="sm" 
              variant="outline" 
              disabled={(data?.current_page ?? 1) >= (data?.last_page ?? 1)} 
              onClick={() => setPage((p) => p + 1)}
            >
              Berikutnya ›
            </Button>
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
    <div className="hover:bg-gray-50 transition-colors">
      <div className="flex items-center gap-3 px-4 py-4 flex-wrap">
        <button 
          onClick={() => setOpen((o) => !o)} 
          className="text-gray-400 hover:text-primary-600 transition-colors"
        >
          {open ? <ChevronDown size={18} /> : <ChevronRight size={18} />}
        </button>
        <button 
          className="inline-flex items-center gap-2 px-3 py-1.5 text-primary-600 hover:text-primary-700 font-semibold text-sm hover:bg-primary-50 transition-all border border-primary-200 hover:border-primary-400 rounded-lg shadow-sm" 
          onClick={() => navigate(`/temuan/${finding.id}`)}
        >
          {finding.registration_number ?? `Draft #${finding.id}`}
        </button>
        <span className="text-sm text-gray-900 flex-1 min-w-[200px]">{finding.title}</span>
        <StatusBadge status={finding.status_label} variant={getFindingStatusVariant(finding.status)} />
        <span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Umur {finding.age_days} hari</span>
        <Can menu="findings" action="delete">
          {finding.status === 'DRAFT' && (
            <Button 
              size="sm" 
              variant="destructive" 
              onClick={() => onDelete(finding)}
            >
              Hapus
            </Button>
          )}
        </Can>
      </div>
      {open && (
        <div className="border-l-2 border-primary-200 ml-8 bg-gray-50/50">
          <ApChildren findingId={finding.id} onOpenFu={onOpenFu} />
        </div>
      )}
    </div>
  )
}

const ApChildren = ({ findingId, onOpenFu }: { findingId: number; onOpenFu: (fu: FollowUp) => void }) => {
  const { data, isLoading } = useFindingTree(findingId)

  if (isLoading) return (
    <div className="flex items-center gap-2 px-4 py-3 text-sm text-gray-500">
      <Spinner /> Memuat action plan...
    </div>
  )
  if (!data || data.length === 0) return (
    <p className="text-sm text-gray-500 px-4 py-3 italic">Belum ada action plan.</p>
  )

  return (
    <div className="divide-y divide-gray-200">
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
      <div className="flex items-center gap-3 px-4 py-3 flex-wrap hover:bg-gray-100/50 transition-colors">
        <button 
          onClick={() => setOpen((o) => !o)} 
          className="text-gray-400 hover:text-primary-600 transition-colors"
        >
          {open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
        </button>
        <button 
          className="inline-flex items-center gap-2 px-3 py-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium hover:bg-primary-50 transition-all border border-primary-200 hover:border-primary-400 rounded-lg shadow-sm" 
          onClick={() => navigate(`/action-plan/${ap.id}`)}
        >
          {ap.code}
        </button>
        <span className="text-sm text-gray-800 flex-1 min-w-[200px]">{ap.title}</span>
        <span className="text-xs text-gray-600 bg-white px-2 py-1 rounded border border-gray-200">{ap.department?.name ?? '-'}</span>
        <StatusBadge status={ap.status_label} variant={getActionPlanStatusVariant(ap.status)} />
        <div className="w-32"><ProgressBar value={ap.progress} /></div>
      </div>
      {open && (
        <div className="border-l-2 border-blue-200 ml-8 bg-blue-50/30">
          {isLoading ? (
            <div className="flex items-center gap-2 px-4 py-2 text-sm text-gray-500">
              <Spinner /> Memuat tindak lanjut...
            </div>
          ) : (followUps?.data ?? []).length === 0 ? (
            <p className="text-sm text-gray-500 px-4 py-2 italic">Belum ada tindak lanjut.</p>
          ) : (
            <div className="divide-y divide-blue-100">
              {(followUps?.data ?? []).map((fu) => (
                <div 
                  key={fu.id} 
                  className="flex items-center gap-3 px-4 py-2 flex-wrap hover:bg-blue-100/50 cursor-pointer transition-colors" 
                  onClick={() => onOpenFu(fu)}
                >
                  <span className="text-sm text-gray-900 flex-1 min-w-[200px]">{fu.description}</span>
                  <span className="text-xs text-gray-600 bg-white px-2 py-1 rounded border border-gray-200">Bobot {fu.weight}%</span>
                  <FollowUpStatusBadge status={fu.status} label={fu.status_label} />
                  <span className="text-xs text-gray-500">Target: {fu.target_date}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}

export default FindingsPage