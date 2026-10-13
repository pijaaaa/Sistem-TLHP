import { cn } from '@/lib/utils'

interface SkeletonProps {
  className?: string
}

const Skeleton = ({ className }: SkeletonProps) => {
  return (
    <div
      className={cn(
        'animate-pulse rounded-md bg-gray-200',
        className
      )}
    />
  )
}

const SkeletonCard = () => {
  return (
    <div className="bg-white rounded-lg shadow p-6 space-y-3">
      <Skeleton className="h-4 w-24" />
      <Skeleton className="h-8 w-16" />
    </div>
  )
}

const SkeletonTable = ({ rows = 5 }: { rows?: number }) => {
  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <div className="p-4 border-b">
        <Skeleton className="h-6 w-48" />
      </div>
      <div className="divide-y">
        {Array.from({ length: rows }).map((_, i) => (
          <div key={i} className="p-4 flex gap-4">
            <Skeleton className="h-4 flex-1" />
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-4 w-16" />
          </div>
        ))}
      </div>
    </div>
  )
}

export { Skeleton, SkeletonCard, SkeletonTable }
