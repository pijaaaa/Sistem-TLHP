import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Plus, ClipboardList } from 'lucide-react'
import { useActionPlans, useSendActionPlans, useChangeDeadline, useDeleteActionPlan } from '@/hooks/useActionPlans'
import { useFindings } from '@/hooks/useFindings'
import { useAuditeeDepartments } from '@/hooks/useLookups'
import { DataTable, PageHeader, Can, ProgressBar, SearchBar, FilterChip, EmptyState } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, StatusBadge, Select, Checkbox, Modal, ConfirmDialog, InputField, Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import { ACTION_PLAN_STATUS_OPTIONS, getActionPlanStatusVariant, type ActionPlan } from '@/types/finding'
import { isAxiosError } from 'axios'
import { motion } from 'framer-motion'

export default function ActionPlansPage() {
  const navigate = useNavigate()
  const { showToast } = useToast()
  const perm = usePermission('action_plans')
  const permFindings = usePermission('findings')
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const [findingId, setFindingId] = useState('')
  const [departmentId, setDepartmentId] = useState('')
  const [selected, setSelected] = useState<number[]>([])
  const [deadlineModal, setDeadlineModal] = useState<ActionPlan | null>(null)
  const [deadline, setDeadline] = useState('')
  const [deadlineReason, setDeadlineReason] = useState('')
  const [deleteItem, setDeleteItem] = useState<ActionPlan | null>(null)

  const { data: findingsPage } = useFindings({ per_page: 100 }, { enabled: permFindings.view })
  const { data: depts } = useAuditeeDepartments()

  const params = useMemo(
    () => ({ page, per_page: 15, status: status || undefined, finding_id: findingId || undefined, department_id: departmentId || undefined }),
    [page, status, findingId, departmentId],
  )
  const { data, isLoading } = useActionPlans(params)
  const sendAps = useSendActionPlans()
  const deadlineHook = useChangeDeadline()
  const removeAp = useDeleteActionPlan()

  const activeFilters = [
    { key: 'status', value: status, label: ACTION_PLAN_STATUS_OPTIONS.find(s => s.value === status)?.label },
    { key: 'findingId', value: findingId, label: `Temuan: ${findingsPage?.data?.find(f => f.id === Number(findingId))?.registration_number}` },
    { key: 'departmentId', value: departmentId, label: depts?.find(d => d.id === Number(departmentId))?.name },
  ].filter(f => f.value)

  const clearFilter = (key: string) => {
    if (key === 'status') setStatus('')
    if (key === 'findingId') setFindingId('')
    if (key === 'departmentId') setDepartmentId('')
    setPage(1)
  }

  const toggleSelect = (id: number) =>
    setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]))

  const sendSelected = async () => {
    if (selected.length === 0) return
    try {
      await sendAps.mutateAsync(selected)
      showToast(`${selected.length} action plan dikirim.`, 'success')
      setSelected([])
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mengirim' : 'Gagal mengirim', 'error')
    }
  }

  const submitDeadline = async () => {
    if (!deadlineModal || !deadline) return
    try {
      await deadlineHook.mutateAsync({ id: deadlineModal.id, deadline, reason: deadlineReason || undefined })
      showToast('Deadline diperbarui.', 'success')
      setDeadlineModal(null)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal memperbarui deadline' : 'Gagal memperbarui deadline', 'error')
    }
  }

  const submitDelete = async () => {
    if (!deleteItem) return
    try {
      await removeAp.mutateAsync(deleteItem.id)
      showToast('Action plan dihapus.', 'success')
      setDeleteItem(null)
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menghapus' : 'Gagal menghapus', 'error')
    }
  }

  const columns: Column<ActionPlan>[] = [
    {
      key: 'select',
      header: '',
      body: (r) => (r.status === 'DRAFT' ? <Checkbox checked={selected.includes(r.id)} onChange={() => toggleSelect(r.id)} /> : null),
    },
    {
      key: 'code',
      header: 'Kode',
      body: (r) => <Link className="text-primary-600 hover:text-primary-700 font-semibold transition-colors" to={`/action-plan/${r.id}`}>{r.code}</Link>,
    },
    { key: 'title', header: 'Judul', body: (r) => <span className="text-sm text-gray-900">{r.title}</span> },
    { key: 'finding', header: 'Temuan', body: (r) => <span className="text-xs text-gray-600">{r.finding?.registration_number ?? `#${r.finding_id}`}</span> },
    { key: 'department', header: 'Departemen', body: (r) => <span className="text-xs text-gray-700 bg-gray-100 px-2 py-1 rounded">{r.department?.name ?? '-'}</span> },
    { key: 'deadline', header: 'Deadline', body: (r) => <span className="text-xs text-gray-600">{r.deadline ?? '-'}</span> },
    { key: 'risk', header: 'Risiko', body: (r) => <span className="text-xs text-gray-600">{r.risk_label ?? '-'}</span> },
    { key: 'progress', header: 'Progres', body: (r) => <div className="w-32"><ProgressBar value={r.progress} /></div> },
    { key: 'status', header: 'Status', body: (r) => <StatusBadge status={r.status_label} variant={getActionPlanStatusVariant(r.status)} /> },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2 flex-wrap">
          <Button size="sm" variant="outline" onClick={() => navigate(`/action-plan/${r.id}`)}>Detail</Button>
          <Can menu="action_plans" action="create">
            {r.status === 'DRAFT' && <Button size="sm" variant="secondary" onClick={() => sendAps.mutateAsync([r.id])}>Kirim</Button>}
          </Can>
          <Can menu="action_plans" action="update">
            {r.status !== 'DRAFT' && r.status !== 'CLOSED' && (
              <Button size="sm" variant="outline" onClick={() => { setDeadline(r.deadline ?? ''); setDeadlineReason(''); setDeadlineModal(r) }}>Ubah Deadline</Button>
            )}
          </Can>
          <Can menu="action_plans" action="delete">
            {r.status === 'DRAFT' && <Button size="sm" variant="destructive" onClick={() => setDeleteItem(r)}>Hapus</Button>}
          </Can>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Action Plan</h1>
          <p className="text-sm text-gray-600 mt-1">Rencana perbaikan per departemen</p>
        </div>
        {perm.create && (
          <Button onClick={() => navigate('/action-plan/baru')} className="gap-2">
            <Plus size={18} />
            Tambah Action Plan
          </Button>
        )}
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }}>
            <option value="">Semua Status</option>
            {ACTION_PLAN_STATUS_OPTIONS.map((s) => (
              <option key={s.value} value={s.value}>{s.label}</option>
            ))}
          </Select>
          {permFindings.view && (
            <Select value={findingId} onChange={(e) => { setFindingId(e.target.value); setPage(1) }}>
              <option value="">Semua Temuan</option>
              {(findingsPage?.data ?? []).map((f) => (
                <option key={f.id} value={f.id}>{f.registration_number ?? `#${f.id}`} — {f.title}</option>
              ))}
            </Select>
          )}
          <Select value={departmentId} onChange={(e) => { setDepartmentId(e.target.value); setPage(1) }}>
            <option value="">Semua Departemen</option>
            {(depts ?? []).map((d) => (
              <option key={d.id} value={d.id}>{d.name}</option>
            ))}
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

        {/* Bulk Actions */}
        {selected.length > 0 && (
          <div className="flex items-center gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <span className="text-sm text-blue-800 font-medium">{selected.length} item terpilih</span>
            <Button variant="secondary" size="sm" onClick={sendSelected}>
              Kirim Terpilih
            </Button>
            <Button variant="outline" size="sm" onClick={() => setSelected([])}>
              Batal
            </Button>
          </div>
        )}
      </div>

      {/* Table */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <Spinner /> Memuat data action plan...
          </div>
        </div>
      ) : (data?.data ?? []).length === 0 ? (
        <motion.div
          initial={{ opacity: 0, scale: 0.95 }}
          animate={{ opacity: 1, scale: 1 }}
        >
          <EmptyState
            icon={ClipboardList}
            title="Tidak ada action plan"
            description={activeFilters.length > 0 ? "Tidak ada action plan yang sesuai dengan filter." : "Belum ada action plan yang terdaftar."}
            action={perm.create ? {
              label: "Tambah Action Plan",
              onClick: () => navigate('/action-plan/baru')
            } : undefined}
          />
        </motion.div>
      ) : (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
        >
          <DataTable
            data={data?.data ?? []}
            columns={columns}
            loading={isLoading}
            pagination={{
              current: data?.current_page ?? 1,
              perPage: data?.per_page ?? 15,
              total: data?.total ?? 0,
              onChange: setPage,
            }}
          />
        </motion.div>
      )}

      <Modal open={!!deadlineModal} onClose={() => setDeadlineModal(null)} title={`Ubah Deadline — ${deadlineModal?.code ?? ''}`}>
        <div className="space-y-4">
          <InputField label="Deadline Baru *" type="date" value={deadline} onChange={(e) => setDeadline(e.target.value)} />
          <InputField label="Alasan (opsional)" value={deadlineReason} onChange={(e) => setDeadlineReason(e.target.value)} />
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setDeadlineModal(null)}>Batal</Button>
            <Button onClick={submitDeadline} disabled={deadlineHook.isPending}>Simpan</Button>
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={!!deleteItem}
        title="Hapus Action Plan?"
        message={`Yakin hapus "${deleteItem?.title}"? Hanya Draft yang dapat dihapus.`}
        variant="danger"
        onConfirm={submitDelete}
        onClose={() => setDeleteItem(null)}
      />
    </div>
  )
}