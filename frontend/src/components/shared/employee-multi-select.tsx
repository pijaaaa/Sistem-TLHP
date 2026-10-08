import type { LookupStaff } from '@/api/findings'

interface EmployeeMultiSelectProps {
  value: number[]
  onChange: (ids: number[]) => void
  staff: LookupStaff[]
  label?: string
  error?: string | null
  disabled?: boolean
}

const EmployeeMultiSelect = ({ value, onChange, staff, label, error, disabled }: EmployeeMultiSelectProps) => {
  const sorted = [...staff].sort((a, b) => a.name.localeCompare(b.name))

  const toggle = (id: number) => {
    onChange(value.includes(id) ? value.filter((v) => v !== id) : [...value, id])
  }

  return (
    <div>
      {label && <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>}
      <div className="border border-gray-300 rounded max-h-60 overflow-y-auto p-3 space-y-1">
        {sorted.length === 0 && <p className="text-sm text-gray-400">Tidak ada staff aktif di departemen ini.</p>}
        {sorted.map((s) => (
          <label key={s.id} className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={value.includes(s.id)}
              onChange={() => toggle(s.id)}
              disabled={disabled}
              className="h-4 w-4 rounded border-gray-300 text-blue-600"
            />
            <span>{s.name}</span>
            <span className="text-xs text-gray-400">@{s.username}</span>
          </label>
        ))}
      </div>
      <p className="text-xs text-gray-500 mt-1">{value.length} PIC dipilih (minimal 1).</p>
      {error && <span className="text-xs text-red-500 mt-1 block">{error}</span>}
    </div>
  )
}

export { EmployeeMultiSelect }