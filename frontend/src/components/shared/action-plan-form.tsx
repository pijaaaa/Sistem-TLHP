import { useEffect, useState } from 'react'
import { FormField, Input, Select, Textarea, Button } from '@/components/ui'
import { DepartmentMultiSelect } from '@/components/shared/department-multi-select'
import { FileUploader, type FileWithLabel } from '@/components/shared/file-uploader'
import { RISK_OPTIONS, type Department, type Finding, type RiskLevel } from '@/types/finding'
import { FileText, AlertTriangle, Calendar, DollarSign, Building2, Upload } from 'lucide-react'

export interface ActionPlanFormValue {
  title: string
  condition: string
  criteria: string
  cause: string
  impact: string
  risk: RiskLevel | ''
  deadline: string
  loss_idr: string
  loss_usd: string
  department_ids: number[]
  deadline_per_department: Record<number, string>
}

const empty = (): ActionPlanFormValue => ({
  title: '',
  condition: '',
  criteria: '',
  cause: '',
  impact: '',
  risk: '',
  deadline: '',
  loss_idr: '',
  loss_usd: '',
  department_ids: [],
  deadline_per_department: {},
})

export interface ActionPlanFormData extends ActionPlanFormValue {
  files: FileWithLabel[]
}

interface ActionPlanFormProps {
  finding: Finding
  departments?: Department[]
  submitting?: boolean
  submitLabel?: string
  onSubmit: (data: ActionPlanFormData) => void
}

const ActionPlanForm = ({ finding, departments, submitting, submitLabel = 'Simpan', onSubmit }: ActionPlanFormProps) => {
  const [form, setForm] = useState<ActionPlanFormValue>(empty())
  const [files, setFiles] = useState<FileWithLabel[]>([])

  const depts = departments ?? finding.auditee_departments ?? []
  const defaultDeadline = finding.response_period_end ?? ''

  useEffect(() => {
    setForm((prev) => ({ ...prev, deadline: prev.deadline || defaultDeadline }))
  }, [defaultDeadline])

  const set = <K extends keyof ActionPlanFormValue>(key: K, v: ActionPlanFormValue[K]) =>
    setForm((prev) => ({ ...prev, [key]: v }))

  const submit = () => {
    if (form.title.trim() === '') return
    if (form.department_ids.length === 0) return
    onSubmit({ ...form, files })
  }

  return (
    <form onSubmit={(e) => { e.preventDefault(); submit() }} className="space-y-6">
      {/* Section: Informasi Temuan */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <FileText size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Informasi Temuan</h3>
        </div>
        
        <FormField label="Temuan Terkait">
          <input 
            disabled 
            value={`${finding.registration_number ?? 'Draft'} — ${finding.title}`} 
            className="flex h-10 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-600" 
          />
        </FormField>
      </div>

      {/* Section: Detail Action Plan */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <FileText size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Detail Action Plan</h3>
        </div>
        
        <FormField label="Judul Action Plan *" required>
          <Input 
            required 
            value={form.title} 
            onChange={(e) => set('title', e.target.value)}
            placeholder="Masukkan judul action plan"
            className="text-base"
          />
        </FormField>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Kondisi (Condition)">
            <Textarea 
              value={form.condition} 
              onChange={(e) => set('condition', e.target.value)} 
              rows={3}
              placeholder="Deskripsi kondisi saat ini"
            />
          </FormField>

          <FormField label="Kriteria (Criteria)">
            <Textarea 
              value={form.criteria} 
              onChange={(e) => set('criteria', e.target.value)} 
              rows={3}
              placeholder="Standar atau kriteria yang digunakan"
            />
          </FormField>

          <FormField label="Sebab (Cause)">
            <Textarea 
              value={form.cause} 
              onChange={(e) => set('cause', e.target.value)} 
              rows={3}
              placeholder="Penyebab terjadinya kondisi"
            />
          </FormField>

          <FormField label="Dampak (Impact)">
            <Textarea 
              value={form.impact} 
              onChange={(e) => set('impact', e.target.value)} 
              rows={3}
              placeholder="Dampak dari kondisi tersebut"
            />
          </FormField>
        </div>
      </div>

      {/* Section: Risiko & Deadline */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <AlertTriangle size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Risiko & Timeline</h3>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Tingkat Risiko">
            <Select value={form.risk} onChange={(e) => set('risk', e.target.value as RiskLevel | '')}>
              <option value="">-- Pilih Risiko --</option>
              {RISK_OPTIONS.map((r) => (
                <option key={r.value} value={r.value}>{r.label}</option>
              ))}
            </Select>
          </FormField>

          <FormField label="Deadline">
            <div className="relative">
              <Calendar size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <Input 
                type="date" 
                value={form.deadline} 
                onChange={(e) => set('deadline', e.target.value)}
                className="pl-10"
              />
            </div>
          </FormField>
        </div>
      </div>

      {/* Section: Potensi Kerugian */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <DollarSign size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Potensi Kerugian</h3>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Nilai (IDR)">
            <Input 
              type="number" 
              min="0" 
              value={form.loss_idr} 
              onChange={(e) => set('loss_idr', e.target.value)} 
              placeholder="0"
            />
          </FormField>

          <FormField label="Nilai (USD)">
            <Input 
              type="number" 
              min="0" 
              step="0.01" 
              value={form.loss_usd} 
              onChange={(e) => set('loss_usd', e.target.value)} 
              placeholder="0.00"
            />
          </FormField>
        </div>
      </div>

      {/* Section: Departemen */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <Building2 size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Departemen Tujuan</h3>
        </div>
        
        <FormField label="Pilih Departemen *" required>
          <DepartmentMultiSelect
            value={form.department_ids}
            onChange={(ids) => set('department_ids', ids)}
            departments={depts}
          />
        </FormField>

        {form.department_ids.length > 1 && (
          <FormField label="Override Deadline per Departemen (opsional)">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
              {depts
                .filter((d) => form.department_ids.includes(d.id))
                .map((d) => (
                  <div key={d.id} className="flex flex-col gap-1">
                    <label className="text-sm font-medium text-gray-700">{d.name}</label>
                    <Input
                      type="date"
                      value={form.deadline_per_department[d.id] ?? form.deadline}
                      onChange={(e) =>
                        set('deadline_per_department', { ...form.deadline_per_department, [d.id]: e.target.value })
                      }
                      size="sm"
                    />
                  </div>
                ))}
            </div>
          </FormField>
        )}
      </div>

      {/* Section: Dokumen */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <Upload size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Dokumen Pendukung</h3>
        </div>
        
        <FormField label="Upload Dokumen (opsional)">
          <FileUploader
            maxFiles={10}
            onFilesChange={setFiles}
            labelPlaceholder="Label dokumen..."
          />
        </FormField>
      </div>

      {/* Actions */}
      <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
        <Button 
          type="button" 
          variant="outline"
          onClick={() => window.history.back()}
        >
          Batal
        </Button>
        <Button 
          type="submit" 
          disabled={submitting || form.department_ids.length === 0}
          className="min-w-32"
        >
          {submitting ? 'Menyimpan...' : submitLabel}
        </Button>
      </div>
    </form>
  )
}

export { ActionPlanForm }