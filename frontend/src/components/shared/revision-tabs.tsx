import { cn } from '@/lib/utils'
import type { SpiRevision } from '@/types/finding'

interface RevisionTabsProps {
  revisions: SpiRevision[]
  active: number
  onChange: (revisionNo: number) => void
}

const RevisionTabs = ({ revisions, active, onChange }: RevisionTabsProps) => {
  const tabs = [{ label: 'Awal', no: 0 }, ...revisions.map((r) => ({ label: r.label, no: r.revision_no }))]

  return (
    <div className="flex gap-1 border-b border-gray-200 overflow-x-auto">
      {tabs.map((t) => (
        <button
          key={t.no}
          onClick={() => onChange(t.no)}
          className={cn(
            'px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap',
            active === t.no ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700',
          )}
        >
          {t.label}
        </button>
      ))}
    </div>
  )
}

export { RevisionTabs }