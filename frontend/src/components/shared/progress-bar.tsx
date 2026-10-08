import { cn } from '@/lib/utils'

const ProgressBar = ({ value, showLabel = true }: { value?: number | string | null; showLabel?: boolean }) => {
  const n = Math.max(0, Math.min(100, Number(value) || 0))
  const done = n >= 100
  return (
    <div className="flex items-center gap-2">
      <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
        <div className={cn('h-2 rounded-full transition-all', done ? 'bg-green-500' : 'bg-blue-500')} style={{ width: `${n}%` }} />
      </div>
      {showLabel && <span className="text-xs text-gray-500 w-10 text-right">{n}%</span>}
    </div>
  )
}

export { ProgressBar }