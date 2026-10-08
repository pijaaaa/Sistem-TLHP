import { useEffect, useState } from 'react'
import { FormField, Input, Select, Textarea, Button } from '@/components/ui'
import { DepartmentMultiSelect } from '@/components/shared/department-multi-select'
import { FileUploader, type FileWithLabel } from '@/components/shared/file-uploader'
import { RISK_OPTIONS, type Department, type Finding, type RiskLevel } from '@/types/finding'

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
    <form onSubmit={(e) => { e.preventDefault(); submit() }} className="grid grid-cols-1 md:grid-cols-2 gap-x-4">
      <div className="md:col-span-2">
        <FormField label="Temuan">
          <input disabled value={`${finding.registration_number ?? 'Draft'} — ${finding.title}`} className="flex h-10 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-600" />
        </FormField>
      </div>

      <div className="md:col-span-2">
        <FormField label="Judul Action Plan *">
          <Input required value={form.title} onChange={(e) => set('title', e.target.value)} />
        </FormField>
      </div>

      <FormField label="Kondisi (condition)">
        <Textarea value={form.condition} onChange={(e) => set('condition', e.target.value)} rows={2} />
      </FormField>

      <FormField label="Kriteria (criteria)">
        <Textarea value={form.criteria} onChange={(e) => set('criteria', e.target.value)} rows={2} />
      </FormField>

      <FormField label="Sebab (cause)">
        <Textarea value={form.cause} onChange={(e) => set('cause', e.target.value)} rows={2} />
      </FormField>

      <FormField label="Dampak (impact)">
        <Textarea value={form.impact} onChange={(e) => set('impact', e.target.value)} rows={2} />
      </FormField>

      <FormField label="Risiko">
        <Select value={form.risk} onChange={(e) => set('risk', e.target.value as RiskLevel | '')}>
          <option value="">-- Pilih Risiko --</option>
          {RISK_OPTIONS.map((r) => (
            <option key={r.value} value={r.value}>{r.label}</option>
          ))}
        </Select>
      </FormField>

      <FormField label="Deadline">
        <Input type="date" value={form.deadline} onChange={(e) => set('deadline', e.target.value)} />
      </FormField>

      <FormField label="Potensi Kerugian (IDR)">
        <Input type="number" min="0" value={form.loss_idr} onChange={(e) => set('loss_idr', e.target.value)} placeholder="0" />
      </FormField>

      <FormField label="Potensi Kerugian (USD)">
        <Input type="number" min="0" step="0.01" value={form.loss_usd} onChange={(e) => set('loss_usd', e.target.value)} placeholder="0" />
      </FormField>

      <div className="md:col-span-2">
        <FormField label="Departemen Tujuan *">
          <DepartmentMultiSelect
            value={form.department_ids}
            onChange={(ids) => set('department_ids', ids)}
            departments={depts}
          />
        </FormField>
      </div>

      {form.department_ids.length > 1 && (
        <div className="md:col-span-2">
          <FormField label="Override Deadline per Departemen (opsional)">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
              {depts
                .filter((d) => form.department_ids.includes(d.id))
                .map((d) => (
                  <label key={d.id} className="flex items-center gap-2 text-sm">
                    <span className="w-40 truncate">{d.name}</span>
                    <Input
                      type="date"
                      value={form.deadline_per_department[d.id] ?? form.deadline}
                      onChange={(e) =>
                        set('deadline_per_department', { ...form.deadline_per_department, [d.id]: e.target.value })
                      }
                    />
                  </label>
                ))}
            </div>
          </FormField>
        </div>
      )}

      <div className="md:col-span-2">
        <FormField label="Dokumen Pendukung (berlabel)">
          <FileUploader
            maxFiles={10}
            onFilesChange={setFiles}
            labelPlaceholder="Label dokumen..."
          />
        </FormField>
      </div>

      <div className="md:col-span-2 flex justify-end pt-2">
        <Button type="submit" disabled={submitting}>{submitting ? 'Menyimpan...' : submitLabel}</Button>
      </div>
    </form>
  )
}

export { ActionPlanForm }