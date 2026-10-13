import { type LucideIcon, TrendingUp, TrendingDown } from 'lucide-react'
import { cn } from '@/lib/utils'

interface StatCardProps {
  label: string
  value: number | string
  icon: LucideIcon
  trend?: number
  trendDirection?: 'up' | 'down'
  iconColor?: string
  iconBgColor?: string
  onClick?: () => void
}

export const StatCard = ({
  label,
  value,
  icon: Icon,
  trend,
  trendDirection,
  iconColor = 'text-primary-600',
  iconBgColor = 'bg-primary-100',
  onClick,
}: StatCardProps) => {
  return (
    <div
      className={cn(
        'bg-white rounded-lg shadow-sm p-6 transition-all duration-200 hover:shadow-md',
        onClick && 'cursor-pointer hover:-translate-y-1'
      )}
      onClick={onClick}
    >
      <div className="flex items-start justify-between">
        <div className="flex-1">
          <p className="text-sm text-gray-600 font-medium mb-1">{label}</p>
          <p className="text-3xl font-bold text-gray-900">{value}</p>
          {trend !== undefined && (
            <div className="flex items-center gap-1 mt-2">
              {trendDirection === 'up' ? (
                <TrendingUp size={16} className="text-green-600" />
              ) : (
                <TrendingDown size={16} className="text-red-600" />
              )}
              <span
                className={cn(
                  'text-xs font-medium',
                  trendDirection === 'up' ? 'text-green-600' : 'text-red-600'
                )}
              >
                {trend > 0 ? '+' : ''}{trend}%
              </span>
              <span className="text-xs text-gray-500">bulan ini</span>
            </div>
          )}
        </div>
        <div className={cn('rounded-full p-3', iconBgColor)}>
          <Icon size={24} className={iconColor} />
        </div>
      </div>
    </div>
  )
}
