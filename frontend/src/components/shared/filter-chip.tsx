import { X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface FilterChipProps {
  label: string
  onRemove: () => void
  className?: string
}

export const FilterChip = ({ label, onRemove, className }: FilterChipProps) => {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-primary-100 text-primary-700 border border-primary-200 transition-all duration-200 hover:bg-primary-200',
        className
      )}
    >
      {label}
      <button
        onClick={onRemove}
        className="hover:bg-primary-300 rounded-full p-0.5 transition-colors"
        type="button"
      >
        <X size={14} />
      </button>
    </span>
  )
}
