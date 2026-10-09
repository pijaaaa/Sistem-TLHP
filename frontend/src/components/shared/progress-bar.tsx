import { cn } from '@/lib/utils'

const ProgressBar = ({ value, showLabel = true }: { value?: number | string | null; showLabel?: boolean }) => {
  const n = Math.max(0, Math.min(100, Number(value) || 0))
  const done = n >= 100
  
  return (
    <div className="flex items-center gap-3 w-full">
      <div className="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden border border-gray-50 shadow-inner">
        <div 
          className={cn(
            'h-full rounded-full transition-all duration-500 ease-out shadow-sm', 
            done ? 'bg-accent-500 shadow-accent-200' : 'bg-primary-600 shadow-primary-200'
          )} 
          style={{ width: `${n}%` }} 
        />
      </div>
      {showLabel && (
        <span className={cn(
          "text-xs font-bold w-10 text-right tabular-nums",
          done ? "text-accent-700" : "text-primary-700"
        )}>
          {n}%
        </span>
      )}
    </div>
  )
}

export { ProgressBar }
