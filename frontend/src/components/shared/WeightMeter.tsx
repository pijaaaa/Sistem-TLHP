interface WeightMeterProps {
  current: number
  total?: number
  showLabel?: boolean
}

export function WeightMeter({ current, total = 100, showLabel = true }: WeightMeterProps) {
  const percentage = (current / total) * 100
  const isValid = Math.abs(current - total) < 0.01
  const color = isValid ? 'bg-green-500' : current > total ? 'bg-red-500' : 'bg-yellow-500'

  return (
    <div className="space-y-2">
      <div className="flex justify-between items-center">
        {showLabel && <span className="text-sm font-medium">Total Bobot</span>}
        <span className={`text-sm font-semibold ${isValid ? 'text-green-600' : current > total ? 'text-red-600' : 'text-yellow-600'}`}>
          {Number(current).toFixed(2)}% / {total}%
        </span>
      </div>
      <div className="w-full bg-gray-200 rounded-full h-2">
        <div className={`h-2 rounded-full transition-all ${color}`} style={{ width: `${Math.min(percentage, 100)}%` }} />
      </div>
      {!isValid && (
        <p className={`text-xs ${current > total ? 'text-red-600' : 'text-yellow-600'}`}>
          {current > total ? 'Bobot melebihi 100%' : `Kurang ${(total - current).toFixed(2)}%`}
        </p>
      )}
    </div>
  )
}
