import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useInbox, useOpenInboxSubject } from '@/hooks/useInbox'
import { DataTable, PageHeader } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Select, StatusBadge } from '@/components/ui'
import { subjectRoute } from '@/lib/links'
import type { InboxTaskItem } from '@/api/findings'

export default function InboxPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const open = useOpenInboxSubject()

  const params = useMemo(() => ({ page, per_page: 15, status: status || undefined }), [page, status])
  const { data, isLoading } = useInbox(params)

  const gotoSubject = async (task: InboxTaskItem) => {
    await open.mutateAsync({ subject_type: task.subject_type, subject_id: task.subject_id })
    navigate(subjectRoute(task.subject_type, task.subject_id))
  }

  const columns: Column<InboxTaskItem>[] = [
    { key: 'title', header: 'Tugas', body: (t) => <button className="text-blue-600 hover:underline text-left" onClick={() => gotoSubject(t)}>{t.title}</button> },
    { key: 'task_type', header: 'Jenis', body: (t) => t.task_type_label },
    { key: 'received_at', header: 'Diterima', body: (t) => (t.received_at ? new Date(t.received_at).toLocaleString('id-ID') : '-') },
    { key: 'first_opened_at', header: 'Pertama Dibuka', body: (t) => (t.first_opened_at ? new Date(t.first_opened_at).toLocaleString('id-ID') : '—') },
    { key: 'acted_at', header: 'Ditindaklanjuti', body: (t) => (t.acted_at ? new Date(t.acted_at).toLocaleString('id-ID') : '—') },
    { key: 'status', header: 'Status', body: (t) => <StatusBadge status={t.status === 'DONE' ? 'Selesai' : 'Terbuka'} /> },
  ]

  return (
    <div className="space-y-4">
      <PageHeader title="Inbox Persetujuan" subtitle="Tugas yang menunggu Anda beserta pelacakan waktunya" />

      <div className="flex items-center gap-3">
        <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-48">
          <option value="">Semua Status</option>
          <option value="OPEN">Terbuka</option>
          <option value="DONE">Selesai</option>
        </Select>
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
    </div>
  )
}