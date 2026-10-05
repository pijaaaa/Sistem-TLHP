import { forwardRef } from 'react'
import { cn } from '@/lib/utils'
import type { InputHTMLAttributes } from 'react'

interface InputFieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string
}

const InputField = forwardRef<HTMLInputElement, InputFieldProps>(({ className, label, type, ...props }, ref) => (
  <div className={cn('mb-4', className)}>
    {label && (
      <label className="block text-sm font-medium text-gray-700 mb-1">
        {label}
      </label>
    )}
    <input
      type={type}
      className={cn(
        'flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500',
      )}
      ref={ref}
      {...props}
    />
  </div>
))
InputField.displayName = 'InputField'

export { InputField }
