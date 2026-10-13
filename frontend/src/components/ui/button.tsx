import { type ButtonHTMLAttributes, forwardRef } from 'react'
import { cn } from '@/lib/utils'

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'outline' | 'destructive' | 'ghost' | 'success'
  size?: 'sm' | 'md' | 'lg'
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'primary', size = 'md', ...props }, ref) => {
    const base = 'inline-flex items-center justify-center rounded-md font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-orange focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 active:scale-95'
    const variants = {
      primary: 'bg-accent-orange text-white hover:bg-accent-orange/90 shadow-sm',
      secondary: 'bg-primary-600 text-white hover:bg-primary-700 shadow-sm',
      outline: 'border-2 border-primary-600 text-primary-600 hover:bg-primary-50',
      destructive: 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
      ghost: 'hover:bg-gray-100 text-gray-700',
      success: 'bg-green-600 text-white hover:bg-green-700 shadow-sm',
    }
    const sizes = {
      sm: 'h-8 px-3 text-sm',
      md: 'h-10 px-4 py-2',
      lg: 'h-12 px-6 text-lg',
    }
    return (
      <button
        className={cn(base, variants[variant], sizes[size], className)}
        ref={ref}
        {...props}
      />
    )
  }
)
Button.displayName = 'Button'

export { Button }
