import { useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useFinding, useRegisterFinding, useActivateFinding, useDeleteFinding, useFindingDocuments, useUploadFindingDocument, useDeleteFindingDocument } from '@/hooks/useFindings'
import { useActionPlans, useSendActionPlans } from '@/hooks/useActionPlans'
import { useFollowUps } from '@/hooks/useFollowUps'
import { useAuditeeDepartments } from '@/hooks/useLookups'
import { findingsApi } from '@/api/findings'
import { PageHeader, DataTable, Can, DocumentPanel, Tabs, DepartmentMultiSelect, FollowUpStatusBadge } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Spinner, StatusBadge, Modal, ConfirmDialog } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { usePermission } from '@/hooks/usePermission'
import { getFindingStatusVariant, getActionPlanStatusVariant, type ActionPlan } from '@/types/finding'
import { isAxiosError, type AxiosError } from 'axios'

export default function FindingDetailPage() {
  const { id } = useParams<{ id: string }>()
  const findingId = Number(id)
  const navigate = useNavigate()
  const { showToast } = useToast()
  const perm = usePermission('findings')
  const auditPerm = usePermission('audit_trail')
  const [tab, setTab] = useState('ringkasan')
  const [registerOpen, setRegisterOpen] = useState(false)
  const [selectedDepts, setSelectedDepts] = useState<number[]>([])
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [activateOpen, setActivateOpen] = useState(false)

  const { data: finding, isLoading } = useFinding(findingId)
  const { data: allDepts } = useAuditeeDepartments()
  const register = useRegisterFinding()
  const activate = useActivateFinding()
  const remove = useDeleteFinding()
  const { data: documents, isLoading: docsLoading } = useFindingDocuments(findingId)
  const uploadDoc = useUploadFindingDocument()
  const deleteDoc = useDeleteFindingDocument()

  const apParams = useMemo(() => ({ per_page: 100, finding_id: findingId }), [findingId])
  const { data: apPage } = useActionPlans(apParams)
  const sendAps = useSendActionPlans()

  const fuParams = useMemo(() => ({ per_page: 100, finding_id: findingId }), [findingId])
  const { data: fuPage } = useFollowUps(fuParams)

  const tabs = [
    { key: 'ringkasan', label: 'Ringkasan' },
    { key: 'dokumen', label: 'Dokumen', badge: finding?.documents_count ?? 0 },
    { key: 'action-plan', label: 'Action Plan', badge: apPage?.total ?? 0 },
    { key: 'tindak-lanjut', label: 'Tindak Lanjut', badge: fuPage?.total ?? 0 },
    { key: 'riwayat', label: 'Riwayat' },
  ]

  const errorMsg = (e: unknown, fallback: string) =>
    isAxiosError(e) ? (e as AxiosError<{ message?: string }>).response?.data?.message ?? fallback : fallback

  const doRegister = async () => {
    if (selectedDepts.length === 0) {
      showToast('Pilih minimal satu departemen auditee.', 'error')
      return
    }
    try {
      await register.mutateAsync({ id: findingId, department_ids: selectedDepts })
      showToast('Temuan berhasil diregistrasi.', 'success')
      setRegisterOpen(false)
    } catch (e) {
      showToast(errorMsg(e, 'Gagal meregistrasi temuan. Periksa kelengkapan data & dokumen LHP.'), 'error')
    }
  }

  const doActivate = async () => {
    try {
      await activate.mutateAsync(findingId)
      showToast('Temuan berhasil diaktifkan.', 'success')
      setActivateOpen(false)
    } catch (e) {
      showToast(errorMsg(e, 'Gagal mengaktifkan temuan.'), 'error')
    }
  }

  const doDelete = async () => {
    try {
      await remove.mutateAsync(findingId)
      showToast('Temuan berhasil dihapus.', 'success')
      navigate('/temuan')
    } catch (e) {
      showToast(errorMsg(e, 'Gagal menghapus temuan.'), 'error')
    }
  }

  const sendAp = async (ap: ActionPlan) => {
    try {
      await sendAps.mutateAsync([ap.id])
      showToast(`Action plan ${ap.code} dikirim.`, 'success')
    } catch (e) {
      showToast(errorMsg(e, 'Gagal mengirim action plan.'), 'error')
    }
  }

  const apColumns: Column<ActionPlan>[] = [
    {
      key: 'code',
      header: 'Kode',
      body: (r) => <Link className="text-blue-600 hover:underline" to={`/action-plan/${r.id}`}>{r.code}</Link>,
    },
    { key: 'title', header: 'Judul', body: (r) => <Link className="text-blue-600 hover:underline" to={`/action-plan/${r.id}`}>{r.title}</Link> },
    { key: 'department', header: 'Departemen', body: (r) => r.department?.name ?? '-' },
    { key: 'risk', header: 'Risiko', body: (r) => r.risk_label ?? '-' },
    { key: 'deadline', header: 'Deadline', body: (r) => r.deadline ?? '-' },
    { key: 'status', header: 'Status', body: (r) => <StatusBadge status={r.status_label} variant={getActionPlanStatusVariant(r.status)} /> },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={() => navigate(`/action-plan/${r.id}`)}>Detail</Button>
          <Can menu="action_plans" action="create">
            {r.status === 'DRAFT' && (
              <Button size="sm" variant="secondary" onClick={() => sendAp(r)}>Kirim</Button>
            )}
          </Can>
        </div>
      ),
    },
  ]

  const registrationMissing = finding && (!finding.registration_number && (finding.documents_count === 0))

  return (
    <div className="space-y-4">
      <PageHeader
        title={finding?.registration_number ?? 'Temuan Draft'}
        subtitle={finding?.title}
        action={
          finding && (
            <div className="flex gap-2 flex-wrap">
              <Can menu="findings" action="update">
                {finding.status === 'DRAFT' && (
                  <>
                    <Button variant="outline" onClick={() => setRegisterOpen(true)}>Registrasi</Button>
                    <Button variant="outline" onClick={() => navigate(`/temuan/${finding.id}/edit`)}>Edit</Button>
                    <Button variant="destructive" onClick={() => setDeleteOpen(true)}>Hapus</Button>
                  </>
                )}
                {finding.status === 'TERDAFTAR' && (
                  <Button onClick={() => setActivateOpen(true)}>Aktifkan</Button>
                )}
              </Can>
            </div>
          )
        }
      />

      {isLoading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
      ) : !finding ? null : (
        <>
          <div className="flex gap-2 items-center">
            <StatusBadge status={finding.status_label} variant={getFindingStatusVariant(finding.status)} />
            {finding.fiscal_year && <span className="text-sm text-gray-500">Tahun Buku {finding.fiscal_year}</span>}
            <span className="text-sm text-gray-500">Umur {finding.age_days} hari</span>
          </div>
          <Tabs tabs={tabs} active={tab} onChange={setTab} />

          {tab === 'ringkasan' && (
            <div className="bg-white rounded-lg shadow p-6 space-y-3">
              {registrationMissing && (
                <p className="text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-3">
                  Unggah dokumen LHP di tab Dokumen sebelum mendaftarkan temuan.
                </p>
              )}
              <DetailRow label="No. LHP" value={finding.lhp_number ?? '-'} />
              <DetailRow label="Tanggal LHP" value={finding.lhp_date ?? '-'} />
              <DetailRow label="Tanggal Temuan" value={finding.finding_date ?? '-'} />
              <DetailRow label="Periode Tanggapan" value={finding.response_period_start && finding.response_period_end ? `${finding.response_period_start} s.d. ${finding.response_period_end}` : '-'} />
              <DetailRow label="Sumber" value={finding.source ? `${finding.source}${finding.source_name ? ' - ' + finding.source_name : ''}` : '-'} />
              <DetailRow label="Ruang Lingkup" value={finding.scope ?? '-'} />
              <DetailRow label="Departemen Auditee" value={(finding.auditee_departments ?? []).map((d) => d.name).join(', ') || '-'} />
              <DetailRow label="Aktif" value={finding.activated_at ? new Date(finding.activated_at).toLocaleString('id-ID') : '-'} />
            </div>
          )}

          {tab === 'dokumen' && (
            <div className="bg-white rounded-lg shadow p-6">
              <DocumentPanel
                documents={documents}
                loading={docsLoading}
                canManage={!!perm.update && finding.status !== 'CLOSED'}
                download={(docId) => findingsApi.downloadDocument(findingId, docId)}
                onUpload={async (file, label) => uploadDoc.mutateAsync({ findingId, file, label })}
                onDelete={async (docId) => deleteDoc.mutateAsync({ findingId, documentId: docId })}
              />
            </div>
          )}

          {tab === 'action-plan' && (
            <div className="bg-white rounded-lg shadow p-6 space-y-4">
              <div className="flex justify-end">
                <Can menu="action_plans" action="create">
                  <Button onClick={() => navigate(`/action-plan/baru?temuan=${finding.id}`)}>+ Tambah Action Plan</Button>
                </Can>
              </div>
              <DataTable data={apPage?.data ?? []} columns={apColumns} loading={!apPage} emptyMessage="Belum ada action plan." />
            </div>
          )}

          {tab === 'tindak-lanjut' && (
            <div className="bg-white rounded-lg shadow p-6">
              {(fuPage?.data ?? []).length === 0 ? (
                <p className="text-sm text-gray-500">Belum ada tindak lanjut yang terlihat.</p>
              ) : (
                <table className="w-full">
                  <thead>
                    <tr className="bg-gray-50">
                      <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Action Plan</th>
                      <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Uraian</th>
                      <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Target</th>
                      <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Bobot</th>
                      <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(fuPage?.data ?? []).map((fu) => (
                      <tr key={fu.id} className="border-t">
                        <td className="px-4 py-2 text-sm">{(fu.action_plan?.code as string) ?? `AP #${fu.action_plan_id}`}</td>
                        <td className="px-4 py-2 text-sm">{fu.description}</td>
                        <td className="px-4 py-2 text-sm whitespace-nowrap">{fu.target_date}</td>
                        <td className="px-4 py-2 text-sm">{fu.weight}</td>
                        <td className="px-4 py-2"><FollowUpStatusBadge status={fu.status} label={fu.status_label} /></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}

          {tab === 'riwayat' && (
            <div className="bg-white rounded-lg shadow p-6 space-y-2 text-sm">
              <p>Dibuat: {finding.created_at ? new Date(finding.created_at).toLocaleString('id-ID') : '-'}</p>
              {finding.activated_at && <p>Aktif: {new Date(finding.activated_at).toLocaleString('id-ID')}</p>}
              {finding.closed_at && <p>Ditutup: {new Date(finding.closed_at).toLocaleString('id-ID')}</p>}
              {!auditPerm.view && <p className="text-gray-500">Log aktivitas rinci tersedia untuk role dengan akses Log Aktivitas.</p>}
            </div>
          )}
        </>
      )}

      <Modal open={registerOpen} onClose={() => setRegisterOpen(false)} title="Registrasi Temuan">
        <div className="space-y-4">
          <p className="text-sm text-gray-600">
            Pilih departemen auditee. Nomor registrasi akan dibuat otomatis.
          </p>
          <DepartmentMultiSelect
            value={selectedDepts}
            onChange={setSelectedDepts}
            departments={allDepts ?? []}
            label="Departemen Auditee *"
          />
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setRegisterOpen(false)}>Batal</Button>
            <Button onClick={doRegister} disabled={register.isPending}>{register.isPending ? 'Mendaftarkan...' : 'Registrasi'}</Button>
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={activateOpen}
        title="Aktifkan Temuan?"
        message="Temuan berpindah ke status Proses Tindak Lanjut. Action plan baru dapat dibuat."
        onConfirm={doActivate}
        onClose={() => setActivateOpen(false)}
      />

      <ConfirmDialog
        open={deleteOpen}
        title="Hapus Temuan?"
        message="Hanya temuan draft yang dapat dihapus. Tindakan tidak dapat dibatalkan."
        variant="danger"
        onConfirm={doDelete}
        onClose={() => setDeleteOpen(false)}
      />
    </div>
  )
}

const DetailRow = ({ label, value }: { label: string; value: string }) => (
  <div className="grid grid-cols-1 md:grid-cols-4 gap-1">
    <span className="text-sm text-gray-500">{label}</span>
    <span className="text-sm text-gray-900 md:col-span-3 whitespace-pre-wrap">{value}</span>
  </div>
)