import { type ReactNode } from 'react'
import { Button } from '@/components/ui'

interface RepeatableFieldsProps<T extends { id: string }> {
  label?: string
  addLabel?: string
  values: T[]
  onChange: (values: T[]) => void
  createEmpty: () => T
  renderRow: (
    row: T,
    index: number,
    update: (patch: Partial<T>) => void,
    remove: () => void,
  ) => ReactNode
}

const RepeatableFields = <T extends { id: string }>({
  label,
  addLabel = '+ Tambah Baris',
  values,
  onChange,
  createEmpty,
  renderRow,
}: RepeatableFieldsProps<T>) => {
  const update = (index: number, patch: Partial<T>) =>
    onChange(values.map((v, i) => (i === index ? { ...v, ...patch } : v)))

  const remove = (index: number) => onChange(values.filter((_, i) => i !== index))

  const add = () => onChange([...values, createEmpty()])

  return (
    <div className="space-y-3">
      {label && <p className="text-sm font-medium text-gray-700">{label}</p>}
      {values.length === 0 && <p className="text-sm text-gray-400">Belum ada baris. Tambahkan minimal satu tindak lanjut.</p>}
      {values.map((row, i) => (
        <div key={row.id} className="border border-gray-200 rounded p-3 bg-gray-50 space-y-2">
          {renderRow(
            row,
            i,
            (patch) => update(i, patch),
            () => remove(i),
          )}
          <div className="flex justify-end">
            <Button type="button" size="sm" variant="destructive" onClick={() => remove(i)}>Hapus Baris</Button>
          </div>
        </div>
      ))}
      <Button type="button" variant="outline" onClick={add}>{addLabel}</Button>
    </div>
  )
}

export { RepeatableFields }