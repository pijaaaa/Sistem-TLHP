import { cn } from '@/lib/utils'

interface Tab {
  id: string
  label: string
  count?: number
}

interface TabsProps {
  tabs: Tab[]
  activeTab: string
  onChange: (id: string) => void
  className?: string
}

const Tabs = ({ tabs, activeTab, onChange, className }: TabsProps) => (
  <div className={cn('border-b border-gray-200 flex flex-wrap', className)}>
    {tabs.map((tab) => {
      const isActive = activeTab === tab.id
      return (
        <button
          key={tab.id}
          onClick={() => onChange(tab.id)}
          className={cn(
            'px-6 py-3 text-sm font-bold transition-all border-b-2 -mb-[2px] flex items-center gap-2',
            isActive
              ? 'border-primary-600 text-primary-800 bg-primary-50/50'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50',
          )}
        >
          {tab.label}
          {tab.count !== undefined && (
            <span className={cn(
              "px-2 py-0.5 rounded-full text-[10px] shadow-sm",
              isActive ? "bg-primary-600 text-white" : "bg-gray-100 text-gray-500"
            )}>
              {tab.count}
            </span>
          )}
        </button>
      )
    })}
  </div>
)

export { Tabs }
