import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ExternalLink, Clock, CheckCircle2 } from 'lucide-react'
import { useInbox, useOpenInboxSubject } from '@/hooks/useInbox'
import { DataTable, PageHeader, FilterChip } from '@/components/shared'
import type { Column } from '@/components/shared/data-table'
import { Select, StatusBadge, Button } from '@/components/ui'
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
    { 
      key: 'title', 
      header: 'Tugas', 
      body: (t) => (
        <button 
          className="inline-flex items-center gap-2 px-3 py-1.5 text-primary-600 hover:text-primary-700 font-medium hover:bg-primary-50 text-left transition-all border border-primary-200 hover:border-primary-400 rounded-lg shadow-sm" 
          onClick={() => gotoSubject(t)}
        >
          <ExternalLink className="w-4 h-4 flex-shrink-0" />
          {t.title}
        </button>
      ) 
    },
    { 
      key: 'task_type', 
      header: 'Jenis', 
      body: (t) => (
        <span className="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
          {t.task_type_label}
        </span>
      ) 
    },
    { 
      key: 'received_at', 
      header: 'Diterima', 
      body: (t) => (
        <div className="flex items-center gap-2 text-sm text-gray-700">
          <Clock className="w-3.5 h-3.5 text-gray-400" />
          {t.received_at ? new Date(t.received_at).toLocaleString('id-ID') : '-'}
        </div>
      ) 
    },
    { 
      key: 'first_opened_at', 
      header: 'Pertama Dibuka', 
      body: (t) => (
        <span className="text-sm text-gray-600">
          {t.first_opened_at ? new Date(t.first_opened_at).toLocaleString('id-ID') : '—'}
        </span>
      ) 
    },
    { 
      key: 'acted_at', 
      header: 'Ditindaklanjuti', 
      body: (t) => (
        <div className="flex items-center gap-2">
          {t.acted_at && <CheckCircle2 className="w-3.5 h-3.5 text-green-600" />}
          <span className="text-sm text-gray-600">
            {t.acted_at ? new Date(t.acted_at).toLocaleString('id-ID') : '—'}
          </span>
        </div>
      ) 
    },
    { 
      key: 'status', 
      header: 'Status', 
      body: (t) => <StatusBadge status={t.status === 'DONE' ? 'Selesai' : 'Terbuka'} /> 
    },
  ]

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <PageHeader title="Inbox Persetujuan" subtitle="Tugas yang menunggu Anda beserta pelacakan waktunya" />

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
        <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-full md:w-48">
          <option value="">Semua Status</option>
          <option value="OPEN">Terbuka</option>
          <option value="DONE">Selesai</option>
        </Select>

        {status && (
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">Filter aktif:</span>
            <FilterChip
              label={status === 'OPEN' ? 'Terbuka' : status === 'DONE' ? 'Selesai' : status}
              onRemove={() => { setStatus(''); setPage(1) }}
            />
          </div>
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
    </div>
  )
}