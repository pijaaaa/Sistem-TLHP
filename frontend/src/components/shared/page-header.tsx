import { type ReactNode } from 'react'
import { cn } from '@/lib/utils'
import { Can } from '@/components/shared'
import type { Action } from '@/types/auth'

interface PageHeaderProps {
  title: string
  subtitle?: string
  action?: ReactNode
  className?: string
  permissionMenu?: string
  permissionAction?: Action
}

const PageHeader = ({
  title,
  subtitle,
  action,
  className,
  permissionMenu,
  permissionAction = 'create',
}: PageHeaderProps) => (
  <div className={cn('flex items-center justify-between mb-6', className)}>
    <div>
      <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
      {subtitle && <p className="text-sm text-gray-500">{subtitle}</p>}
    </div>
    {action && (
      <div>
        {permissionMenu ? (
          <Can menu={permissionMenu} action={permissionAction}>
            {action}
          </Can>
        ) : (
          action
        )}
      </div>
    )}
  </div>
)

export { PageHeader }
