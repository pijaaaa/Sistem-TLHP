import { useEffect, useState } from 'react'
import { FormField, Input, Select, Textarea, Button } from '@/components/ui'
import { SOURCE_OPTIONS, type FindingPayload } from '@/types/finding'

interface FindingFormProps {
  initial?: Partial<FindingPayload>
  submitLabel?: string
  submitting?: boolean
  onSubmit: (payload: FindingPayload) => void
}

const blank = (): FindingPayload => ({
  title: '',
  source: '',
  source_name: null,
  lhp_number: null,
  lhp_date: null,
  finding_date: null,
  response_period_start: null,
  response_period_end: null,
  scope: null,
})

const FindingForm = ({ initial, submitLabel = 'Simpan', submitting, onSubmit }: FindingFormProps) => {
  const [form, setForm] = useState<FindingPayload>(blank())

  useEffect(() => {
    if (initial) setForm({ ...blank(), ...initial })
  }, [initial])

  const set = <K extends keyof FindingPayload>(key: K, v: FindingPayload[K]) =>
    setForm((prev) => ({ ...prev, [key]: v }))

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        onSubmit(form)
      }}
      className="grid grid-cols-1 md:grid-cols-2 gap-x-4"
    >
      <div className="md:col-span-2">
        <FormField label="Judul Temuan *">
          <Input required value={form.title ?? ''} onChange={(e) => set('title', e.target.value)} />
        </FormField>
      </div>

      <FormField label="Sumber *">
        <Select value={form.source ?? ''} onChange={(e) => set('source', e.target.value)}
          data-testid="source-select"
          required>
          <option value="">-- Pilih Sumber --</option>
          {SOURCE_OPTIONS.map((s) => (
            <option key={s.value} value={s.value}>{s.label}</option>
          ))}
        </Select>
      </FormField>

      {form.source === 'LAINNYA' && (
        <FormField label="Nama Sumber *">
          <Input required value={form.source_name ?? ''} onChange={(e) => set('source_name', e.target.value)} placeholder="contoh: KAP Indonesia" />
        </FormField>
      )}

      <FormField label="Nomor LHP">
        <Input value={form.lhp_number ?? ''} onChange={(e) => set('lhp_number', e.target.value)} />
      </FormField>

      <FormField label="Tanggal LHP">
        <Input type="date" value={form.lhp_date ?? ''} onChange={(e) => set('lhp_date', e.target.value || null)} />
      </FormField>

      <FormField label="Tanggal Temuan">
        <Input type="date" value={form.finding_date ?? ''} onChange={(e) => set('finding_date', e.target.value || null)} />
      </FormField>

      <FormField label="Awal Periode Tanggapan">
        <Input type="date" value={form.response_period_start ?? ''} onChange={(e) => set('response_period_start', e.target.value || null)} />
      </FormField>

      <FormField label="Akhir Periode Tanggapan">
        <Input type="date" value={form.response_period_end ?? ''} onChange={(e) => set('response_period_end', e.target.value || null)} />
      </FormField>

      <div className="md:col-span-2">
        <FormField label="Ruang Lingkup">
          <Textarea value={form.scope ?? ''} onChange={(e) => set('scope', e.target.value)} rows={3} />
        </FormField>
      </div>

      <div className="md:col-span-2 flex justify-end pt-2">
        <Button type="submit" disabled={submitting}>{submitting ? 'Menyimpan...' : submitLabel}</Button>
      </div>
    </form>
  )
}

export { FindingForm }