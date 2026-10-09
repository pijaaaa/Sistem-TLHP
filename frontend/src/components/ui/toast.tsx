import { cn } from '@/lib/utils'

interface ToastProps {
  message: string
  type?: 'success' | 'error' | 'info'
  className?: string
}

const Toast = ({ message, type = 'info', className }: ToastProps) => {
  const styles = {
    success: 'bg-accent-50 border-accent-200 text-accent-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    info: 'bg-primary-50 border-primary-200 text-primary-800',
  }

  return (
    <div className={cn('p-4 rounded-xl border text-sm font-medium shadow-sm flex items-center gap-3', styles[type], className)}>
      <span>{message}</span>
    </div>
  )
}

export { Toast }
