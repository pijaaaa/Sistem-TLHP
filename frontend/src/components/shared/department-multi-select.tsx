import { useMemo, useState } from 'react'
import type { Department } from '@/types/finding'

interface DepartmentMultiSelectProps {
  value: number[]
  onChange: (ids: number[]) => void
  departments: Department[]
  label?: string
  error?: string | null
  disabled?: boolean
}

const DepartmentMultiSelect = ({ value, onChange, departments, label, error, disabled }: DepartmentMultiSelectProps) => {
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    const list = departments.filter((d) => !q || d.name.toLowerCase().includes(q) || d.code.toLowerCase().includes(q))
    return [...list].sort((a, b) => a.name.localeCompare(b.name))
  }, [departments, query])

  const allSelected = filtered.length > 0 && filtered.every((d) => value.includes(d.id))

  const toggle = (id: number) => {
    onChange(value.includes(id) ? value.filter((v) => v !== id) : [...value, id])
  }

  const toggleAll = () => {
    if (allSelected) {
      const ids = new Set(value)
      filtered.forEach((d) => ids.delete(d.id))
      onChange([...ids])
    } else {
      const ids = new Set(value)
      filtered.forEach((d) => ids.add(d.id))
      onChange([...ids])
    }
  }

  return (
    <div>
      {label && <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>}
      <div className="border border-gray-300 rounded">
        <button
          type="button"
          onClick={() => setOpen(!open)}
          disabled={disabled}
          className="w-full text-left px-3 py-2 text-sm bg-gray-50 hover:bg-gray-100 rounded flex items-center justify-between"
        >
          <span className={value.length ? '' : 'text-gray-400'}>
            {value.length ? `${value.length} departemen dipilih` : 'Pilih departemen...'}
          </span>
          <span>{open ? '▲' : '▼'}</span>
        </button>

        {open && (
          <div className="p-3 space-y-2">
            <input
              type="text"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Cari departemen..."
              className="w-full px-3 py-2 text-sm border border-gray-300 rounded"
            />
            <label className="flex items-center gap-2 text-sm font-medium">
              <input
                type="checkbox"
                checked={allSelected}
                disabled={filtered.length === 0}
                onChange={toggleAll}
                className="h-4 w-4 rounded border-gray-300 text-blue-600"
              />
              Pilih semua
            </label>
            <div className="max-h-48 overflow-y-auto space-y-1">
              {filtered.map((d) => (
                <label key={d.id} className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={value.includes(d.id)}
                    onChange={() => toggle(d.id)}
                    className="h-4 w-4 rounded border-gray-300 text-blue-600"
                  />
                  <span>{d.name}</span>
                  <span className="text-xs text-gray-400">{d.code}</span>
                </label>
              ))}
              {filtered.length === 0 && (
                <p className="text-sm text-gray-400">Tidak ada departemen yang cocok.</p>
              )}
            </div>
          </div>
        )}
      </div>
      {error && <span className="text-xs text-red-500 mt-1 block">{error}</span>}
    </div>
  )
}

export { DepartmentMultiSelect }