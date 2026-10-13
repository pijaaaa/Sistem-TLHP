import { type ReactNode } from 'react'
import { cn } from '@/lib/utils'

type BadgeVariant = 'default' | 'success' | 'warning' | 'danger' | 'info'

interface BadgeProps {
  variant?: BadgeVariant
  className?: string
  children: ReactNode
}

function getStatusVariant(status?: string | null): BadgeVariant {
  if (!status) return 'default'
  const normalized = status.toLowerCase()
  if (normalized.includes('close') || normalized.includes('approved') || normalized.includes('selesai') || normalized.includes('disetujui') || normalized.includes('ssr')) return 'success'
  if (normalized.includes('warning') || normalized.includes('pending') || normalized.includes('menunggu') || normalized.includes('draft')) return 'warning'
  if (normalized.includes('reject') || normalized.includes('ditolak') || normalized.includes('error') || normalized.includes('bsr') || normalized.includes('tidak dapat')) return 'danger'
  if (normalized.includes('process') || normalized.includes('proses') || normalized.includes('diajukan') || normalized.includes('dikirim') || normalized.includes('didistribusikan')) return 'info'
  return 'default'
}

const Badge = ({ variant = 'default', className, children }: BadgeProps) => {
  const variants: Record<BadgeVariant, string> = {
    default: 'bg-gray-100 text-gray-800 border border-gray-200',
    success: 'bg-green-100 text-green-700 border border-green-200',
    warning: 'bg-accent-yellow/20 text-yellow-800 border border-accent-yellow/30',
    danger: 'bg-red-100 text-red-700 border border-red-200',
    info: 'bg-primary-50 text-primary-700 border border-primary-200',
  }
  return (
    <span className={cn('px-3 py-1 rounded-full text-xs font-medium', variants[variant], className)}>
      {children}
    </span>
  )
}

const StatusBadge = ({ status, variant }: { status?: string | null; variant?: BadgeVariant }) => {
  return <Badge variant={variant ?? getStatusVariant(status)}>{status ?? '-'}</Badge>
}

export { Badge, StatusBadge }
