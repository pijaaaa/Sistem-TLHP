import { type LucideIcon } from 'lucide-react'
import { AvatarCircle } from './avatar-circle'
import { cn } from '@/lib/utils'

interface TimelineItemProps {
  icon?: LucideIcon
  avatarName?: string
  avatarPhoto?: string | null
  title: string
  description?: string
  timestamp: string
  isLast?: boolean
}

export const TimelineItem = ({
  icon: Icon,
  avatarName,
  avatarPhoto,
  title,
  description,
  timestamp,
  isLast = false,
}: TimelineItemProps) => {
  return (
    <div className="flex gap-4">
      <div className="flex flex-col items-center">
        {avatarName ? (
          <AvatarCircle name={avatarName} photoUrl={avatarPhoto} size="md" />
        ) : Icon ? (
          <div className="rounded-full bg-primary-100 p-2">
            <Icon size={20} className="text-primary-600" />
          </div>
        ) : (
          <div className="h-10 w-10 rounded-full bg-gray-200" />
        )}
        {!isLast && <div className="w-0.5 flex-1 bg-gray-200 mt-2" />}
      </div>
      <div className={cn('flex-1 pb-8', isLast && 'pb-0')}>
        <h4 className="text-sm font-semibold text-gray-900">{title}</h4>
        {description && (
          <p className="text-sm text-gray-600 mt-1">{description}</p>
        )}
        <p className="text-xs text-gray-400 mt-1">{timestamp}</p>
      </div>
    </div>
  )
}
