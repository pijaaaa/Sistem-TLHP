import { cn } from '@/lib/utils'

const Spinner = ({ className }: { className?: string }) => (
  <div className={cn('inline-block animate-spin rounded-full border-2 border-primary-600 border-t-transparent h-5 w-5', className)} />
)

export { Spinner }
