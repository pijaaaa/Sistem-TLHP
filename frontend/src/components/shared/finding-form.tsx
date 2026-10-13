import { useEffect, useState } from 'react'
import { FormField, Input, Select, Textarea, Button } from '@/components/ui'
import { useFindingSources } from '@/hooks/useLookups'
import type { FindingPayload } from '@/types/finding'
import { FileText, Calendar, FileSearch } from 'lucide-react'

interface FindingFormProps {
  initial?: Partial<FindingPayload>
  submitLabel?: string
  submitting?: boolean
  onSubmit: (payload: FindingPayload) => void
}

const blank = (): FindingPayload => ({
  title: '',
  source: '',
  lhp_number: null,
  lhp_date: null,
  finding_date: null,
  response_period_start: null,
  response_period_end: null,
  scope: null,
})

const FindingForm = ({ initial, submitLabel = 'Simpan', submitting, onSubmit }: FindingFormProps) => {
  const [form, setForm] = useState<FindingPayload>(blank())
  const { data: sources } = useFindingSources()

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
      className="space-y-6"
    >
      {/* Section: Informasi Temuan */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <FileText size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Informasi Temuan</h3>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="md:col-span-2">
            <FormField label="Judul Temuan *" required>
              <Input 
                required 
                value={form.title ?? ''} 
                onChange={(e) => set('title', e.target.value)}
                placeholder="Masukkan judul temuan"
                className="text-base"
              />
            </FormField>
          </div>

          <div className="md:col-span-2">
            <FormField label="Sumber *" required>
              <Select 
                value={form.source ?? ''} 
                onChange={(e) => set('source', e.target.value)}
                data-testid="source-select"
                required
                className="text-base"
              >
                <option value="">-- Pilih Sumber --</option>
                {(sources ?? []).map((s) => (
                  <option key={s.code} value={s.code}>{s.name}</option>
                ))}
              </Select>
            </FormField>
          </div>

          <div className="md:col-span-2">
            <FormField label="Ruang Lingkup">
              <Textarea 
                value={form.scope ?? ''} 
                onChange={(e) => set('scope', e.target.value)} 
                rows={4}
                placeholder="Deskripsi ruang lingkup temuan (opsional)"
                className="text-base"
              />
            </FormField>
          </div>
        </div>
      </div>

      {/* Section: Detail LHP */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <FileSearch size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Detail LHP</h3>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Nomor LHP">
            <Input 
              value={form.lhp_number ?? ''} 
              onChange={(e) => set('lhp_number', e.target.value)}
              placeholder="Contoh: LHP/001/2026"
            />
          </FormField>

          <FormField label="Tanggal LHP">
            <Input 
              type="date" 
              value={form.lhp_date ?? ''} 
              onChange={(e) => set('lhp_date', e.target.value || null)} 
            />
          </FormField>

          <FormField label="Tanggal Temuan">
            <Input 
              type="date" 
              value={form.finding_date ?? ''} 
              onChange={(e) => set('finding_date', e.target.value || null)} 
            />
          </FormField>
        </div>
      </div>

      {/* Section: Periode Tanggapan */}
      <div className="space-y-4">
        <div className="flex items-center gap-2 pb-2 border-b border-gray-200">
          <Calendar size={20} className="text-primary-600" />
          <h3 className="text-lg font-semibold text-gray-900">Periode Tanggapan</h3>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Tanggal Mulai">
            <Input 
              type="date" 
              value={form.response_period_start ?? ''} 
              onChange={(e) => set('response_period_start', e.target.value || null)} 
            />
          </FormField>

          <FormField label="Tanggal Akhir">
            <Input 
              type="date" 
              value={form.response_period_end ?? ''} 
              onChange={(e) => set('response_period_end', e.target.value || null)} 
            />
          </FormField>
        </div>
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
          disabled={submitting}
          className="min-w-32"
        >
          {submitting ? 'Menyimpan...' : submitLabel}
        </Button>
      </div>
    </form>
  )
}

export { FindingForm }