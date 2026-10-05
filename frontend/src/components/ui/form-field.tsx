import { type ReactNode } from 'react'
import { cn } from '@/lib/utils'

interface FormFieldProps {
  label?: string
  name?: string
  error?: string
  className?: string
  children: ReactNode
}

const FormField = ({ label, name, error, className, children }: FormFieldProps) => (
  <div className={cn('mb-4', className)}>
    {label && (
      <label htmlFor={name} className="block text-sm font-medium text-gray-700 mb-1">
        {label}
      </label>
    )}
    {children}
    {error && <span className="text-xs text-red-500 mt-1 block">{error}</span>}
  </div>
)

export { FormField }
