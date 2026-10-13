import { cn } from '@/lib/utils'

interface AvatarCircleProps {
  name: string
  photoUrl?: string | null
  size?: 'sm' | 'md' | 'lg'
  className?: string
}

const sizeClasses = {
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-12 w-12 text-base',
}

const getInitials = (name: string): string => {
  const parts = name.trim().split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

export const AvatarCircle = ({ 
  name, 
  photoUrl, 
  size = 'md', 
  className 
}: AvatarCircleProps) => {
  if (photoUrl) {
    return (
      <img
        src={photoUrl}
        alt={name}
        className={cn(
          'rounded-full object-cover',
          sizeClasses[size],
          className
        )}
      />
    )
  }

  return (
    <div
      className={cn(
        'rounded-full bg-primary-600 text-white font-semibold flex items-center justify-center',
        sizeClasses[size],
        className
      )}
      title={name}
    >
      {getInitials(name)}
    </div>
  )
}
