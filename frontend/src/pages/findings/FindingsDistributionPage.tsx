import { useState } from 'react'
import { usePendingDistributions, useDistributeFinding } from '@/hooks/useFindingDistribution'
import { useDepartments } from '@/hooks/useDepartments'
import { DataTable, PageHeader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Button, Modal, StatusBadge } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import type { Finding } from '@/types/finding'
import type { Department } from '@/types/master'
import { getFindingStatusLabel } from '@/types/finding'
import { isAxiosError } from 'axios'

export default function FindingsDistributionPage() {
  const { showToast } = useToast()
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [selectedFinding, setSelectedFinding] = useState<Finding | null>(null)
  const [selectedDepts, setSelectedDepts] = useState<number[]>([])

  const { data, isLoading } = usePendingDistributions({ page, per_page: 15 })
  const { data: deptData, isLoading: deptLoading } = useDepartments()
  const distribute = useDistributeFinding()

  const openDistribute = (finding: Finding) => {
    setSelectedFinding(finding)
    setSelectedDepts([])
    setModalOpen(true)
  }

  const handleSubmit = async () => {
    if (!selectedFinding || selectedDepts.length === 0) {
      showToast('Pilih setidaknya satu departemen', 'error')
      return
    }
    try {
      await distribute.mutateAsync({ findingId: selectedFinding.id, departmentIds: selectedDepts })
      showToast('Temuan berhasil didistribusikan ke departemen', 'success')
      setModalOpen(false)
    } catch (e) {
      showToast(
        isAxiosError(e) ? e.response?.data?.message ?? 'Gagal mendistribusikan' : 'Gagal mendistribusikan',
        'error',
      )
    }
  }

  const columns: Column<Finding>[] = [
    { key: 'code', header: 'Kode', body: (r) => r.code },
    { key: 'title', header: 'Judul', body: (r) => r.title },
    {
      key: 'severity',
      header: 'Tingkat',
      body: (r) => <StatusBadge status={r.severity ?? 'medium'} />,
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
        <Button size="sm" variant="outline" onClick={() => openDistribute(r)}>
          Distribusikan
        </Button>
      ),
    },
  ]

  const departments = deptData?.data ?? []

  return (
    <div className="space-y-4">
      <PageHeader
        title="Distribusi Temuan"
        subtitle="Distribusikan temuan ke departemen"
        permissionMenu="findings.distribution"
        permissionAction="create"
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
        permissionMenu="findings.distribution"
        permissionAction="view"
        emptyMessage="Tidak ada temuan yang perlu didistribusikan"
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={`Distribusi: ${selectedFinding?.code ?? ''}`}
      >
        <div className="space-y-3 max-h-[60vh] overflow-y-auto">
          <p className="text-sm text-gray-600">Pilih departemen untuk distribusi temuan ini:</p>

          {deptLoading ? (
            <p className="text-sm text-gray-500">Memuat departemen...</p>
          ) : (
            <div className="space-y-2">
              {departments.map((dept: Department) => (
                <label key={dept.id} className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={selectedDepts.includes(dept.id)}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedDepts([...selectedDepts, dept.id])
                      } else {
                        setSelectedDepts(selectedDepts.filter((id) => id !== dept.id))
                      }
                    }}
                  />
                  <span>{dept.name} ({dept.code})</span>
                </label>
              ))}
            </div>
          )}

          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => setModalOpen(false)}>
              Batal
            </Button>
            <Button
              onClick={handleSubmit}
              disabled={distribute.isPending || selectedDepts.length === 0}
            >
              {distribute.isPending ? 'Menyimpan...' : 'Kirim'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
