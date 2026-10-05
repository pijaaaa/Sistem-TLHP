import { type LabelHTMLAttributes, forwardRef } from 'react'
import { cn } from '@/lib/utils'

const Label = forwardRef<HTMLLabelElement, LabelHTMLAttributes<HTMLLabelElement>>(
  ({ className, children, ...props }, ref) => (
    <label
      className={cn('text-sm font-medium text-gray-700', className)}
      ref={ref}
      {...props}
    >
      {children}
    </label>
  )
)
Label.displayName = 'Label'

export { Label }
