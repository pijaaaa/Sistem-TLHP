import { cn } from '@/lib/utils'

interface WeightMeterProps {
  value: number
  total: number
  className?: string
}

const WeightMeter = ({ value, total, className }: WeightMeterProps) => {
  const percentage = Math.min(100, (value / total) * 100)
  const isFull = percentage >= 100

  return (
    <div className={cn('space-y-1', className)}>
      <div className="flex justify-between text-xs">
        <span className="font-bold text-gray-500">Bobot Terpakai</span>
        <span className={cn("font-bold", isFull ? "text-accent-600" : "text-primary-700")}>
          {value} / {total}
        </span>
      </div>
      <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden shadow-inner">
        <div
          className={cn(
            'h-full transition-all duration-500',
            isFull ? 'bg-accent-500' : 'bg-primary-600'
          )}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  )
}

export { WeightMeter }
