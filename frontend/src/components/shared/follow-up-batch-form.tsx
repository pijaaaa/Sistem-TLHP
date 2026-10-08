import { useMemo, useState } from 'react'
import { FormField, Input, Textarea, Button, Select } from '@/components/ui'
import { RepeatableFields } from '@/components/shared/repeatable-fields'
import { EmployeeMultiSelect } from '@/components/shared/employee-multi-select'
import { WeightMeter } from '@/components/shared/WeightMeter'
import type { ActionPlan, ActionPlanUser, FollowUpRowInput } from '@/types/finding'

interface Row extends FollowUpRowInput {
  id: string
  weight: number | ''
}

export type SubmitMode = 'draft' | 'submit'

interface FollowUpBatchFormProps {
  actionPlan: ActionPlan
  linkCandidates?: { id: number; label: string }[]
  onSubmit: (rows: FollowUpRowInput[], mode: SubmitMode) => void
  submitting?: boolean
}

const picOptions = (ap: ActionPlan): { id: number; name: string; username: string }[] =>
  (ap.assignees ?? []).map((u: ActionPlanUser) => ({ id: u.id, name: u.name, username: u.username }))

const FollowUpBatchForm = ({ actionPlan, linkCandidates, onSubmit, submitting }: FollowUpBatchFormProps) => {
  const deadline = actionPlan.deadline ?? ''
  const [rows, setRows] = useState<Row[]>([])

  const total = useMemo(() => rows.reduce((sum, r) => sum + (Number(r.weight) || 0), 0), [rows])
  const hasExceeded = total > 100
  const hasInvalidDate = rows.some((r) => deadline && r.target_date && r.target_date > deadline)
  const hasEmpty = rows.some((r) => !r.description.trim() || r.weight === '' || r.pic_ids.length === 0 || !r.target_date)

  const pics = picOptions(actionPlan)

  const submit = (m: SubmitMode) => {
    if (rows.length === 0 || hasExceeded || hasInvalidDate || hasEmpty) return
    onSubmit(
      rows.map(({ id, ...rest }) => rest),
      m,
    )
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        submit('submit')
      }}
      className="space-y-4"
    >
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div className="text-sm text-gray-500">
          Deadline action plan: <span className="font-medium text-gray-800">{deadline || '-'}</span>
        </div>
        <div className="w-56">
          <WeightMeter current={total} />
        </div>
      </div>

      {hasInvalidDate && (
        <p className="text-sm text-red-600">Ada target tanggal yang melewati deadline action plan.</p>
      )}
      {hasExceeded && (
        <p className="text-sm text-red-600">Total bobot melebihi 100. Kurangi bobot beberapa baris.</p>
      )}

      <RepeatableFields
        label="Daftar Tindak Lanjut"
        values={rows}
        onChange={setRows}
        addLabel="+ Tambah Tindak Lanjut"
        createEmpty={() => ({ id: crypto.randomUUID(), description: '', target_date: '', weight: '', pic_ids: [], linked_follow_up_id: null })}
        renderRow={(row, _i, update, _remove) => (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <FormField label="Uraian *">
                <Textarea
                  rows={2}
                  value={row.description}
                  onChange={(e) => update({ description: e.target.value })}
                />
              </FormField>
              <div className="space-y-3">
                <FormField label="Tanggal Target *">
                  <Input
                    type="date"
                    value={row.target_date}
                    min=""
                    max={deadline || undefined}
                    onChange={(e) => update({ target_date: e.target.value })}
                  />
                  {deadline && row.target_date && row.target_date > deadline && (
                    <span className="text-xs text-red-500">Melebihi deadline ({deadline}).</span>
                  )}
                </FormField>
                <FormField label="Bobot (integer, 1–100) *">
                  <Input
                    type="number"
                    min={1}
                    max={100}
                    step={1}
                    value={row.weight}
                    onChange={(e) => update({ weight: e.target.value === '' ? '' : Number(e.target.value) })}
                  />
                </FormField>
              </div>
            </div>
            {linkCandidates && linkCandidates.length > 0 && (
              <FormField label="Tautan ke Tindak Lanjut Revisi Sebelumnya (opsional)">
                <Select
                  value={row.linked_follow_up_id ?? ''}
                  onChange={(e) => update({ linked_follow_up_id: e.target.value ? Number(e.target.value) : null })}
                >
                  <option value="">-- Tidak ada / buat baru --</option>
                  {linkCandidates.map((c) => (
                    <option key={c.id} value={c.id}>{c.label}</option>
                  ))}
                </Select>
              </FormField>
            )}
            <FormField label="PIC *">
              <EmployeeMultiSelect
                value={row.pic_ids}
                onChange={(ids) => update({ pic_ids: ids })}
                staff={pics}
                label=""
              />
            </FormField>
          </>
        )}
      />

      <div className="flex justify-end gap-3 pt-2">
        <Button
          type="button"
          variant="outline"
          disabled={submitting || rows.length === 0 || hasExceeded || hasInvalidDate || hasEmpty}
          onClick={() => submit('draft')}
        >
          Simpan Draft
        </Button>
        <Button
          type="submit"
          disabled={submitting || rows.length === 0 || hasExceeded || hasInvalidDate || hasEmpty}
        >
          {submitting ? 'Menyimpan...' : 'Ajukan ke Manager'}
        </Button>
      </div>
    </form>
  )
}

export { FollowUpBatchForm }