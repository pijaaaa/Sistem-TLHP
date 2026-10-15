import { useMemo, useState } from 'react'
import { FormField, Input, Textarea, Button, Select } from '@/components/ui'
import { RepeatableFields } from '@/components/shared/repeatable-fields'
import { EmployeeMultiSelect } from '@/components/shared/employee-multi-select'
import { WeightMeter } from '@/components/shared/WeightMeter'
import type { ActionPlan, ActionPlanUser, FollowUpRowInput } from '@/types/finding'
import { ClipboardList, AlertCircle, CheckCircle2 } from 'lucide-react'

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
  existingWeight?: number
}

const picOptions = (ap: ActionPlan): { id: number; name: string; username: string }[] =>
  (ap.assignees ?? []).map((u: ActionPlanUser) => ({ id: u.id, name: u.name, username: u.username }))

const FollowUpBatchForm = ({ actionPlan, linkCandidates, onSubmit, submitting, existingWeight = 0 }: FollowUpBatchFormProps) => {
  const deadline = actionPlan.deadline ?? ''
  const [rows, setRows] = useState<Row[]>([])

  const newWeightTotal = useMemo(() => rows.reduce((sum, r) => sum + (Number(r.weight) || 0), 0), [rows])
  const total = existingWeight + newWeightTotal
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
      className="space-y-6"
    >
      {/* Header Info */}
      <div className="flex items-center gap-2 pb-3 border-b border-gray-200">
        <ClipboardList size={20} className="text-primary-600" />
        <h3 className="text-lg font-semibold text-gray-900">Daftar Tindak Lanjut</h3>
      </div>

      {/* Deadline & Weight Meter */}
      <div className="bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg p-4 border border-primary-200">
        <div className="flex items-center justify-between flex-wrap gap-4">
          <div className="flex items-center gap-2">
            <AlertCircle size={18} className="text-primary-600" />
            <div>
              <p className="text-xs text-gray-600">Deadline Action Plan</p>
              <p className="text-sm font-semibold text-gray-900">{deadline || 'Tidak ada'}</p>
            </div>
          </div>
          <div className="flex-1 max-w-xs">
            <p className="text-xs text-gray-600 mb-1">Total Bobot</p>
            <WeightMeter current={total} />
          </div>
        </div>
      </div>

      {/* Validation Messages */}
      {hasInvalidDate && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-2">
          <AlertCircle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
          <p className="text-sm text-red-700">Ada target tanggal yang melewati deadline action plan.</p>
        </div>
      )}
      {hasExceeded && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-2">
          <AlertCircle size={18} className="text-red-600 flex-shrink-0 mt-0.5" />
          <p className="text-sm text-red-700">Total bobot melebihi 100. Kurangi bobot beberapa baris. (Bobot yang sudah ada: {existingWeight}%)</p>
        </div>
      )}
      {!hasEmpty && !hasExceeded && !hasInvalidDate && rows.length > 0 && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-3 flex items-start gap-2">
          <CheckCircle2 size={18} className="text-green-600 flex-shrink-0 mt-0.5" />
          <p className="text-sm text-green-700">Semua field telah diisi dengan benar dan siap diajukan.</p>
        </div>
      )}

      {/* Repeatable Fields */}
      <RepeatableFields
        label=""
        values={rows}
        onChange={setRows}
        addLabel="+ Tambah Tindak Lanjut"
        createEmpty={() => ({ id: crypto.randomUUID(), description: '', target_date: '', weight: '', pic_ids: [], linked_follow_up_id: null })}
        renderRow={(row, _i, update, _remove) => (
          <div className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField label="Uraian Kegiatan *" required>
                <Textarea
                  rows={3}
                  value={row.description}
                  onChange={(e) => update({ description: e.target.value })}
                  placeholder="Jelaskan kegiatan tindak lanjut yang akan dilakukan"
                  className="text-sm"
                />
              </FormField>
              <div className="space-y-4">
                <FormField label="Tanggal Target *" required>
                  <Input
                    type="date"
                    value={row.target_date}
                    min=""
                    max={deadline || undefined}
                    onChange={(e) => update({ target_date: e.target.value })}
                  />
                  {deadline && row.target_date && row.target_date > deadline && (
                    <span className="text-xs text-red-500 flex items-center gap-1 mt-1">
                      <AlertCircle size={12} />
                      Melebihi deadline ({deadline})
                    </span>
                  )}
                </FormField>
                <FormField label="Bobot (%) *" required>
                  <Input
                    type="number"
                    min={1}
                    max={100}
                    step={1}
                    value={row.weight}
                    onChange={(e) => update({ weight: e.target.value === '' ? '' : Number(e.target.value) })}
                    placeholder="1-100"
                  />
                </FormField>
              </div>
            </div>

            {linkCandidates && linkCandidates.length > 0 && (
              <FormField label="Tautan ke Tindak Lanjut Revisi Sebelumnya (opsional)">
                <Select
                  value={row.linked_follow_up_id ?? ''}
                  onChange={(e) => update({ linked_follow_up_id: e.target.value ? Number(e.target.value) : null })}
                  className="text-sm"
                >
                  <option value="">-- Tidak ada / buat baru --</option>
                  {linkCandidates.map((c) => (
                    <option key={c.id} value={c.id}>{c.label}</option>
                  ))}
                </Select>
              </FormField>
            )}

            <FormField label="Penanggung Jawab (PIC) *" required>
              <EmployeeMultiSelect
                value={row.pic_ids}
                onChange={(ids) => update({ pic_ids: ids })}
                staff={pics}
                label=""
              />
            </FormField>
          </div>
        )}
      />

      {rows.length === 0 && (
        <div className="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <ClipboardList size={40} className="mx-auto text-gray-400 mb-2" />
          <p className="text-sm text-gray-600">Belum ada tindak lanjut.</p>
          <p className="text-xs text-gray-500 mt-1">Klik tombol "Tambah Tindak Lanjut" untuk memulai.</p>
        </div>
      )}

      {/* Progress Indicator */}
      {rows.length > 0 && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <p className="text-sm text-blue-800">
            <strong>{rows.length}</strong> tindak lanjut telah ditambahkan
          </p>
        </div>
      )}

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
          type="button"
          variant="secondary"
          disabled={submitting || rows.length === 0 || hasExceeded || hasInvalidDate || hasEmpty}
          onClick={() => submit('draft')}
        >
          Simpan Draft
        </Button>
        <Button
          type="submit"
          disabled={submitting || rows.length === 0 || hasExceeded || hasInvalidDate || hasEmpty}
          className="min-w-40"
        >
          {submitting ? 'Menyimpan...' : 'Ajukan ke Manager'}
        </Button>
      </div>
    </form>
  )
}

export { FollowUpBatchForm }