import { useState } from 'react'
import { PageHeader, Tabs, ProgressBar } from '@/components/shared'
import { Select, InputField, Button, Spinner } from '@/components/ui'
import { useReportDepartments, useReportLate, useReportAge, useReportRisk } from '@/hooks/useReports'
import { useAuditeeDepartments } from '@/hooks/useLookups'
import { SOURCE_OPTIONS } from '@/types/finding'
import { apiClient } from '@/api/client'
import { confirmDownload } from '@/lib/utils'

type TabKey = 'departments' | 'late' | 'age' | 'risk'

const EXPORT_LINKS: Record<TabKey, string | null> = {
  departments: '/exports/reports/departments',
  late: '/exports/reports/late',
  risk: '/exports/reports/risk',
  age: null,
}

export default function ReportsPage() {
  const [tab, setTab] = useState<TabKey>('departments')
  const [fiscalYear, setFiscalYear] = useState('')
  const [source, setSource] = useState('')
  const [departmentId, setDepartmentId] = useState('')

  const filters = { fiscal_year: fiscalYear || undefined, source: source || undefined, department_id: departmentId || undefined }

  const { data: deptsData, isLoading: deptsLoading } = useReportDepartments(tab === 'departments' ? filters : undefined)
  const { data: lateData, isLoading: lateLoading } = useReportLate(tab === 'late' ? filters : undefined)
  const { data: ageData, isLoading: ageLoading } = useReportAge(tab === 'age' ? filters : undefined)
  const { data: riskData, isLoading: riskLoading } = useReportRisk(tab === 'risk' ? filters : undefined)

  const { data: depts } = useAuditeeDepartments()
  const loading = tab === 'departments' ? deptsLoading : tab === 'late' ? lateLoading : tab === 'age' ? ageLoading : riskLoading

  const exportHref = EXPORT_LINKS[tab]
  const download = (path: string) => confirmDownload(`${apiClient.defaults.baseURL}${path}`)

  return (
    <div className="space-y-4">
      <PageHeader title="Monitoring & Laporan" subtitle="Rekap dan analisis seluruh temuan, action plan, dan tindak lanjut" />

      <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
        <Select value={fiscalYear} onChange={(e) => setFiscalYear(e.target.value)}>
          <option value="">Semua Tahun</option>
          {Array.from({ length: 8 }, (_, i) => new Date().getFullYear() - i).map((y) => <option key={y} value={y}>{y}</option>)}
        </Select>
        <Select value={source} onChange={(e) => setSource(e.target.value)}>
          <option value="">Semua Sumber</option>
          {SOURCE_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </Select>
        <Select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)}>
          <option value="">Semua Departemen</option>
          {(depts ?? []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
        </Select>
        {exportHref && <Button variant="outline" onClick={() => download(exportHref)}>Export Excel (CSV)</Button>}
      </div>

      <Tabs
        tabs={[
          { key: 'departments', label: 'Rekap Departemen' },
          { key: 'late', label: 'Keterlambatan' },
          { key: 'age', label: 'Umur Temuan' },
          { key: 'risk', label: 'Risiko' },
        ]}
        active={tab}
        onChange={(k) => setTab(k as TabKey)}
      />

      {loading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
      ) : tab === 'departments' ? (
        <DepartmentTable data={(deptsData?.data ?? []) as Record<string, unknown>[]} />
      ) : tab === 'late' ? (
        <LateTable data={(lateData?.data ?? []) as Record<string, unknown>[]} />
      ) : tab === 'age' ? (
        <AgeList data={(ageData?.data ?? []) as { group: string; count: number }[]} />
      ) : (
        <RiskTable data={(riskData?.data ?? []) as { risk: string; risk_label: string; count: number; loss_idr: number; loss_usd: number }[]} />
      )}
    </div>
  )
}

const DepartmentTable = ({ data }: { data: Record<string, unknown>[] }) => (
  <div className="bg-white rounded-lg shadow overflow-x-auto">
    <table className="w-full">
      <thead>
        <tr className="bg-gray-50 text-left text-xs text-gray-600">
          <th className="px-4 py-2">Departemen</th>
          <th className="px-4 py-2">Action Plan</th>
          <th className="px-4 py-2">Tindak Lanjut</th>
          <th className="px-4 py-2">Progres Rata-rata</th>
          <th className="px-4 py-2">Selesai</th>
          <th className="px-4 py-2">Terlambat</th>
        </tr>
      </thead>
      <tbody>
        {data.length === 0 ? <tr><td colSpan={6} className="px-4 py-4 text-sm text-gray-500">Tidak ada data.</td></tr> : data.map((r, i) => (
          <tr key={i} className="border-t">
            <td className="px-4 py-2 text-sm">{String(r.department)}</td>
            <td className="px-4 py-2 text-sm">{Number(r.total_action_plans)}</td>
            <td className="px-4 py-2 text-sm">{Number(r.total_follow_ups)}</td>
            <td className="px-4 py-2 w-40"><ProgressBar value={Number(r.avg_progress)} /></td>
            <td className="px-4 py-2 text-sm">{Number(r.selesai)}</td>
            <td className="px-4 py-2 text-sm text-red-600">{Number(r.terlambat)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
)

const LateTable = ({ data }: { data: Record<string, unknown>[] }) => (
  <div className="bg-white rounded-lg shadow overflow-x-auto">
    <table className="w-full">
      <thead>
        <tr className="bg-gray-50 text-left text-xs text-gray-600">
          <th className="px-4 py-2">Temuan</th>
          <th className="px-4 py-2">Action Plan</th>
          <th className="px-4 py-2">Departemen</th>
          <th className="px-4 py-2">Tindak Lanjut</th>
          <th className="px-4 py-2">Target</th>
          <th className="px-4 py-2">Hari Terlambat</th>
        </tr>
      </thead>
      <tbody>
        {data.length === 0 ? <tr><td colSpan={6} className="px-4 py-4 text-sm text-gray-500">Tidak ada keterlambatan.</td></tr> : data.map((r, i) => (
          <tr key={i} className="border-t">
            <td className="px-4 py-2 text-sm">{String(r.finding)}</td>
            <td className="px-4 py-2 text-sm">{String(r.action_plan_code)}</td>
            <td className="px-4 py-2 text-sm">{String(r.department)}</td>
            <td className="px-4 py-2 text-sm">{String(r.description)}</td>
            <td className="px-4 py-2 text-sm">{String(r.target_date)}</td>
            <td className="px-4 py-2 text-sm text-red-600">{Number(r.days_overdue)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
)

const AgeList = ({ data }: { data: { group: string; count: number }[] }) => (
  <div className="bg-white rounded-lg shadow p-4">
    {data.length === 0 ? <p className="text-sm text-gray-500">Tidak ada data.</p> : (
      <ul className="space-y-1">
        {data.map((r) => (
          <li key={r.group} className="flex items-center gap-3 text-sm">
            <span className="w-32">{r.group}</span>
            <ProgressBar value={(r.count / Math.max(1, Math.max(...data.map((d) => d.count)))) * 100} showLabel={false} />
            <span className="font-medium">{r.count}</span>
          </li>
        ))}
      </ul>
    )}
  </div>
)

const RiskTable = ({ data }: { data: { risk: string; risk_label: string; count: number; loss_idr: number; loss_usd: number }[] }) => (
  <div className="bg-white rounded-lg shadow overflow-x-auto">
    <table className="w-full">
      <thead>
        <tr className="bg-gray-50 text-left text-xs text-gray-600">
          <th className="px-4 py-2">Risiko</th>
          <th className="px-4 py-2">Jumlah AP</th>
          <th className="px-4 py-2">Potensi Kerugian (IDR)</th>
          <th className="px-4 py-2">Potensi Kerugian (USD)</th>
        </tr>
      </thead>
      <tbody>
        {data.map((r) => (
          <tr key={r.risk ?? '-'} className="border-t">
            <td className="px-4 py-2 text-sm">{r.risk_label}</td>
            <td className="px-4 py-2 text-sm">{r.count}</td>
            <td className="px-4 py-2 text-sm">{Number(r.loss_idr).toLocaleString('id-ID')}</td>
            <td className="px-4 py-2 text-sm">{Number(r.loss_usd).toLocaleString('id-ID')}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
)