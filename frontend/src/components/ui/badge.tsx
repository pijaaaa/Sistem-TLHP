import { type ReactNode } from 'react'
import { cn } from '@/lib/utils'

type BadgeVariant = 'default' | 'success' | 'warning' | 'danger' | 'info'

interface BadgeProps {
  variant?: BadgeVariant
  className?: string
  children: ReactNode
}

function getStatusVariant(status: string): BadgeVariant {
  const normalized = status.toLowerCase()
  if (normalized.includes('close') || normalized.includes('approved') || normalized.includes('selesai')) return 'success'
  if (normalized.includes('warning') || normalized.includes('pending')) return 'warning'
  if (normalized.includes('reject') || normalized.includes('ditolak') || normalized.includes('error')) return 'danger'
  if (normalized.includes('process') || normalized.includes('proses')) return 'info'
  return 'default'
}

const Badge = ({ variant = 'default', className, children }: BadgeProps) => {
  const variants: Record<BadgeVariant, string> = {
    default: 'bg-gray-100 text-gray-800',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800',
    info: 'bg-blue-100 text-blue-800',
  }
  return (
    <span className={cn('px-2 py-1 rounded-full text-xs font-medium', variants[variant], className)}>
      {children}
    </span>
  )
}

const StatusBadge = ({ status }: { status: string }) => {
  const variant = getStatusVariant(status)
  return <Badge variant={variant}>{status}</Badge>
}

export { Badge, StatusBadge }
