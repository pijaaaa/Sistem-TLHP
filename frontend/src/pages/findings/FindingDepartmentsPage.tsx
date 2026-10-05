import { useState } from 'react'
import { useFindingDepartments } from '@/hooks/useFindingDistribution'
import { useUsers } from '@/hooks/useUsers'
import { DataTable, PageHeader, Can } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, StatusBadge } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { FindingDepartment } from '@/types/finding'
import type { AccountUser } from '@/types/master'
import { getFindingDepartmentStatusLabel } from '@/types/finding'
import { isAxiosError } from 'axios'

export default function FindingDepartmentsPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [assigningFd, setAssigningFd] = useState<FindingDepartment | null>(null)
  const [selectedPics, setSelectedPics] = useState<number[]>([])

  const { data, isLoading, refetch } = useFindingDepartments({ page, per_page: 15 })

  const openAssignPics = (fd: FindingDepartment) => {
    setAssigningFd(fd)
    setSelectedPics([])
    setModalOpen(true)
  }

  const handleSubmit = async () => {
    if (!assigningFd || selectedPics.length === 0) {
      showToast('Pilih setidaknya satu PIC', 'error')
      return
    }
    try {
      const res = await import('@/hooks/useFindingDistribution').then((m) => m.useAssignPics())
      await res.mutateAsync({ fdId: assigningFd.id, picIds: selectedPics })
      showToast('PIC berhasil ditugaskan', 'success')
      setModalOpen(false)
      refetch()
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menugaskan PIC' : 'Gagal menugaskan PIC',
        'error',
      )
    }
  }

  const columns: Column<FindingDepartment>[] = [
    {
      key: 'finding',
      header: 'Temuan',
      body: (r) => r.finding?.code ?? r.finding_id,
    },
    {
      key: 'department',
      header: 'Departemen',
      body: (r) => r.department?.name ?? r.department_id,
    },
    {
      key: 'status',
      header: 'Status',
      body: (r) => <StatusBadge status={getFindingDepartmentStatusLabel(r.status)} />,
    },
    {
      key: 'pics',
      header: 'PIC',
      body: (r) => r.pics?.map((p) => p.name).join(', ') ?? '-',
    },
    {
      key: 'actions',
      header: 'Aksi',
      body: (r) => (
        <Can menu="findings.list" action="update">
          {r.status === 'diterima' && (
            <Button size="sm" variant="outline" onClick={() => openAssignPics(r)}>
              Assign PIC
            </Button>
          )}
        </Can>
      ),
    },
  ]

  return (
    <div className="space-y-4">
      <PageHeader
        title="Daftar Temuan"
        subtitle="Temuan yang didistribusikan ke departemen"
        permissionMenu="findings.list"
        permissionAction="view"
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
        permissionMenu="findings.list"
        permissionAction="view"
        emptyMessage="Tidak ada temuan yang didistribusikan"
      />

      <AssignPicsModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        departmentId={assigningFd?.department_id}
        selectedPics={selectedPics}
        onPicsChange={setSelectedPics}
        onSubmit={handleSubmit}
        loading={false}
      />
    </div>
  )
}

function AssignPicsModal({
  open,
  onClose,
  departmentId,
  selectedPics,
  onPicsChange,
  onSubmit,
  loading,
}: {
  open: boolean
  onClose: () => void
  departmentId?: number
  selectedPics: number[]
  onPicsChange: (ids: number[]) => void
  onSubmit: () => void
  loading: boolean
}) {
  const [picData, setPicData] = useState<AccountUser[]>([])

  const loadPics = async () => {
    const { usersApi } = await import('@/api/master')
    const res = await usersApi.list({ per_page: 100 })
    const allUsers: AccountUser[] = res.data
    if (departmentId) {
      setPicData(allUsers.filter((u) => u.department?.id === departmentId && u.role === 'staff_dept'))
    } else {
      setPicData([])
    }
  }

  if (open && picData.length === 0 && !loading) {
    loadPics()
  }

  return (
    <Modal open={open} onClose={onClose} title="Assign PIC">
      <div className="space-y-3 max-h-[60vh] overflow-y-auto">
        <p className="text-sm text-gray-600">Pilih PIC dari departemen yang sesuai:</p>
        {picData.length > 0 ? (
          <div className="space-y-2">
            {picData.map((pic: AccountUser) => (
              <label key={pic.id} className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={selectedPics.includes(pic.id)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      onPicsChange([...selectedPics, pic.id])
                    } else {
                      onPicsChange(selectedPics.filter((id) => id !== pic.id))
                    }
                  }}
                />
                <span>{pic.name} ({pic.username})</span>
              </label>
            ))}
          </div>
        ) : (
          <p className="text-sm text-gray-500">Memuat PIC...</p>
        )}
        <div className="flex justify-end gap-2 pt-2">
          <Button variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button onClick={onSubmit} disabled={loading || selectedPics.length === 0}>
            {loading ? 'Menyimpan...' : 'Simpan'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}
