import { type ReactNode } from 'react'
import { cn } from '@/lib/utils'

interface TabsProps {
  tabs: { key: string; label: string; badge?: number }[]
  active: string
  onChange: (key: string) => void
  className?: string
  children?: ReactNode
}

const Tabs = ({ tabs, active, onChange, className }: TabsProps) => (
  <div className={cn('border-b border-gray-200', className)}>
    <nav className="flex gap-1 overflow-x-auto">
      {tabs.map((t) => (
        <button
          key={t.key}
          onClick={() => onChange(t.key)}
          className={cn(
            'px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap',
            active === t.key
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700',
          )}
        >
          {t.label}
          {t.badge ? <span className="ml-1 text-xs text-gray-400">({t.badge})</span> : null}
        </button>
      ))}
    </nav>
  </div>
)

export { Tabs }