import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useActionPlans, useSendActionPlans, useChangeDeadline, useDeleteActionPlan } from '@/hooks/useActionPlans'
import { useFindings } from '@/hooks/useFindings'
import { useAuditeeDepartments } from '@/hooks/useLookups'
import { DataTable, PageHeader, Can, ProgressBar } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, StatusBadge, Select, Checkbox, Modal, ConfirmDialog, InputField } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import { ACTION_PLAN_STATUS_OPTIONS, getActionPlanStatusVariant, type ActionPlan } from '@/types/finding'
import { isAxiosError } from 'axios'

export default function ActionPlansPage() {
  const navigate = useNavigate()
  const { showToast } = useToast()
  const perm = usePermission('action_plans')
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const [findingId, setFindingId] = useState('')
  const [departmentId, setDepartmentId] = useState('')
  const [selected, setSelected] = useState<number[]>([])
  const [deadlineModal, setDeadlineModal] = useState<ActionPlan | null>(null)
  const [deadline, setDeadline] = useState('')
  const [deadlineReason, setDeadlineReason] = useState('')
  const [deleteItem, setDeleteItem] = useState<ActionPlan | null>(null)

  const { data: findingsPage } = useFindings({ per_page: 100 })
  const { data: depts } = useAuditeeDepartments()

  const params = useMemo(
    () => ({ page, per_page: 15, status: status || undefined, finding_id: findingId || undefined, department_id: departmentId || undefined }),
    [page, status, findingId, departmentId],
  )
  const { data, isLoading } = useActionPlans(params)
  const sendAps = useSendActionPlans()
  const deadlineHook = useChangeDeadline()
  const removeAp = useDeleteActionPlan()

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
      body: (r) => <Link className="text-blue-600 hover:underline" to={`/action-plan/${r.id}`}>{r.code}</Link>,
    },
    { key: 'title', header: 'Judul', body: (r) => <Link className="text-blue-600 hover:underline" to={`/action-plan/${r.id}`}>{r.title}</Link> },
    { key: 'finding', header: 'Temuan', body: (r) => r.finding?.registration_number ?? `#${r.finding_id}` },
    { key: 'department', header: 'Departemen', body: (r) => r.department?.name ?? '-' },
    { key: 'deadline', header: 'Deadline', body: (r) => r.deadline ?? '-' },
    { key: 'risk', header: 'Risiko', body: (r) => r.risk_label ?? '-' },
    { key: 'progress', header: 'Progres', body: (r) => <ProgressBar value={r.progress} /> },
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
    <div className="space-y-4">
      <PageHeader
        title="Action Plan"
        subtitle="Rencana perbaikan per departemen"
        permissionMenu="action_plans"
        permissionAction="create"
        action={perm.create ? <Button onClick={() => navigate('/action-plan/baru')}>+ Tambah Action Plan</Button> : undefined}
      />

      <div className="flex items-center gap-3 flex-wrap">
        <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-56">
          <option value="">Semua Status</option>
          {ACTION_PLAN_STATUS_OPTIONS.map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
        <Select value={findingId} onChange={(e) => { setFindingId(e.target.value); setPage(1) }} className="w-64">
          <option value="">Semua Temuan</option>
          {(findingsPage?.data ?? []).map((f) => (
            <option key={f.id} value={f.id}>{f.registration_number ?? `#${f.id}`} — {f.title}</option>
          ))}
        </Select>
        <Select value={departmentId} onChange={(e) => { setDepartmentId(e.target.value); setPage(1) }} className="w-56">
          <option value="">Semua Departemen</option>
          {(depts ?? []).map((d) => (
            <option key={d.id} value={d.id}>{d.name}</option>
          ))}
        </Select>

        {selected.length > 0 && (
          <Button variant="secondary" onClick={sendSelected}>Kirim {selected.length} Terpilih</Button>
        )}
      </div>

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