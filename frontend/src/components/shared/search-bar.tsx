import { Search } from 'lucide-react'
import { cn } from '@/lib/utils'

interface SearchBarProps {
  value: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
}

export const SearchBar = ({ 
  value, 
  onChange, 
  placeholder = 'Cari...', 
  className 
}: SearchBarProps) => {
  return (
    <div className={cn('relative', className)}>
      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <Search size={20} className="text-gray-400" />
      </div>
      <input
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-accent-orange focus:border-transparent transition-all duration-200"
      />
    </div>
  )
}
