import { useState } from 'react'
import { PageHeader, Tabs, ProgressBar, FilterChip, EmptyState } from '@/components/shared'
import { Select, Button, Spinner } from '@/components/ui'
import { useReportDepartments, useReportLate, useReportAge, useReportRisk } from '@/hooks/useReports'
import { useAuditeeDepartments, useFindingSources } from '@/hooks/useLookups'
import { apiClient } from '@/api/client'
import { confirmDownload } from '@/lib/utils'
import { motion } from 'framer-motion'
import { Download, FileText } from 'lucide-react'

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
  const { data: sources } = useFindingSources()
  const loading = tab === 'departments' ? deptsLoading : tab === 'late' ? lateLoading : tab === 'age' ? ageLoading : riskLoading

  const exportHref = EXPORT_LINKS[tab]
  const download = (path: string) => confirmDownload(`${apiClient.defaults.baseURL}${path}`)

  const activeFilters = [
    { key: 'fiscalYear', value: fiscalYear, label: `Tahun ${fiscalYear}` },
    { key: 'source', value: source, label: sources?.find(s => s.code === source)?.name },
    { key: 'departmentId', value: departmentId, label: depts?.find(d => d.id === Number(departmentId))?.name },
  ].filter(f => f.value)

  const clearFilter = (key: string) => {
    if (key === 'fiscalYear') setFiscalYear('')
    if (key === 'source') setSource('')
    if (key === 'departmentId') setDepartmentId('')
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <PageHeader title="Monitoring & Laporan" subtitle="Rekap dan analisis seluruh temuan, action plan, dan tindak lanjut" />

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <Select value={fiscalYear} onChange={(e) => setFiscalYear(e.target.value)}>
            <option value="">Semua Tahun</option>
            {Array.from({ length: 8 }, (_, i) => new Date().getFullYear() - i).map((y) => <option key={y} value={y}>{y}</option>)}
          </Select>
          <Select value={source} onChange={(e) => setSource(e.target.value)}>
            <option value="">Semua Sumber</option>
            {(sources ?? []).map((s) => <option key={s.code} value={s.code}>{s.name}</option>)}
          </Select>
          <Select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)}>
            <option value="">Semua Departemen</option>
            {(depts ?? []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
          </Select>
        </div>
        
        <div className="flex items-center justify-between gap-4">
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
           {exportHref && (
             <Button variant="secondary" onClick={() => download(exportHref)} className="ml-auto flex items-center gap-2">
               <Download size={16} /> Export Excel
             </Button>
           )}
        </div>
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
        <div className="flex items-center justify-center py-12">
            <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat laporan...</div>
        </div>
      ) : (
        <motion.div
           initial={{ opacity: 0, y: 10 }}
           animate={{ opacity: 1, y: 0 }}
           transition={{ duration: 0.3 }}
        >
          {tab === 'departments' ? (
            <DepartmentTable data={(deptsData ?? []) as Record<string, unknown>[]} />
          ) : tab === 'late' ? (
            <LateTable data={(lateData ?? []) as Record<string, unknown>[]} />
          ) : tab === 'age' ? (
            <AgeList data={(ageData ?? []) as { group: string; count: number }[]} />
          ) : (
            <RiskTable data={(riskData ?? []) as { risk: string; risk_label: string; count: number; loss_idr: number; loss_usd: number }[]} />
          )}
        </motion.div>
      )}
    </div>
  )
}

const DepartmentTable = ({ data }: { data: Record<string, unknown>[] }) => (
  <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table className="w-full">
      <thead className="bg-gray-50 border-b border-gray-200">
        <tr className="text-left text-xs uppercase tracking-wider text-gray-600">
          <th className="px-6 py-3">Departemen</th>
          <th className="px-6 py-3">Action Plan</th>
          <th className="px-6 py-3">Tindak Lanjut</th>
          <th className="px-6 py-3">Progres Rata-rata</th>
          <th className="px-6 py-3">Selesai</th>
          <th className="px-6 py-3 text-red-600">Terlambat</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-gray-200">
        {data.length === 0 ? <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data.</td></tr> : data.map((r, i) => (
          <tr key={i} className="hover:bg-gray-50 transition-colors">
            <td className="px-6 py-4 text-sm font-medium text-gray-900">{String(r.department)}</td>
            <td className="px-6 py-4 text-sm text-gray-600">{Number(r.total_action_plans)}</td>
            <td className="px-6 py-4 text-sm text-gray-600">{Number(r.total_follow_ups)}</td>
            <td className="px-6 py-4 w-40"><ProgressBar value={Number(r.avg_progress)} /></td>
            <td className="px-6 py-4 text-sm font-medium text-green-600">{Number(r.selesai)}</td>
            <td className="px-6 py-4 text-sm font-bold text-red-600">{Number(r.terlambat)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
)

const LateTable = ({ data }: { data: Record<string, unknown>[] }) => (
  <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table className="w-full">
      <thead className="bg-gray-50 border-b border-gray-200">
        <tr className="text-left text-xs uppercase tracking-wider text-gray-600">
          <th className="px-6 py-3">Temuan</th>
          <th className="px-6 py-3">Action Plan</th>
          <th className="px-6 py-3">Departemen</th>
          <th className="px-6 py-3">Tindak Lanjut</th>
          <th className="px-6 py-3">Target</th>
          <th className="px-6 py-3 text-red-600">Hari Terlambat</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-gray-200">
        {data.length === 0 ? <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-500">Tidak ada keterlambatan.</td></tr> : data.map((r, i) => (
          <tr key={i} className="hover:bg-gray-50 transition-colors">
            <td className="px-6 py-4 text-sm font-medium text-gray-900">{String(r.finding)}</td>
            <td className="px-6 py-4 text-sm text-primary-600 font-semibold">{String(r.action_plan_code)}</td>
            <td className="px-6 py-4 text-sm text-gray-600">{String(r.department)}</td>
            <td className="px-6 py-4 text-sm text-gray-600">{String(r.description)}</td>
            <td className="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{String(r.target_date)}</td>
            <td className="px-6 py-4 text-sm font-bold text-red-600">{Number(r.days_overdue)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
)

const AgeList = ({ data }: { data: { group: string; count: number }[] }) => (
  <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
    {data.length === 0 ? (
        <EmptyState icon={FileText} title="Tidak ada data" />
    ) : (
      <div className="space-y-4">
        {data.map((r) => (
          <div key={r.group} className="space-y-1">
            <div className="flex justify-between text-sm">
                <span className="font-medium text-gray-700">{r.group}</span>
                <span className="font-bold text-primary-700">{r.count}</span>
            </div>
            <ProgressBar value={(r.count / Math.max(1, Math.max(...data.map((d) => d.count)))) * 100} />
          </div>
        ))}
      </div>
    )}
  </div>
)

const RiskTable = ({ data }: { data: { risk: string; risk_label: string; count: number; loss_idr: number; loss_usd: number }[] }) => (
  <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table className="w-full">
      <thead className="bg-gray-50 border-b border-gray-200">
        <tr className="text-left text-xs uppercase tracking-wider text-gray-600">
          <th className="px-6 py-3">Risiko</th>
          <th className="px-6 py-3">Jumlah AP</th>
          <th className="px-6 py-3">Potensi Kerugian (IDR)</th>
          <th className="px-6 py-3">Potensi Kerugian (USD)</th>
        </tr>
      </thead>
      <tbody className="divide-y divide-gray-200">
        {data.map((r) => (
          <tr key={r.risk ?? '-'} className="hover:bg-gray-50 transition-colors">
            <td className="px-6 py-4 text-sm font-medium text-gray-900">{r.risk_label}</td>
            <td className="px-6 py-4 text-sm text-gray-700">{r.count}</td>
            <td className="px-6 py-4 text-sm text-gray-700 font-mono">Rp {Number(r.loss_idr).toLocaleString('id-ID')}</td>
            <td className="px-6 py-4 text-sm text-gray-700 font-mono">$ {Number(r.loss_usd).toLocaleString('id-ID', { minimumFractionDigits: 2 })}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
)
