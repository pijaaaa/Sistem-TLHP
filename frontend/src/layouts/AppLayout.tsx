import { type ReactNode, useState } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { cn } from '@/lib/utils'

const AppLayout = ({ children }: { children?: ReactNode }) => {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()

  const menuItems = [
    { label: 'Dashboard', path: '/' },
    { label: 'Temuan', path: '/findings' },
    { label: 'Tindak Lanjut', path: '/action-plans' },
    { label: 'Dev Components', path: '/dev/components' },
  ]

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Sidebar */}
      <aside className={cn(
        'bg-gray-900 text-white transition-transform duration-200',
        sidebarOpen ? 'w-64' : 'w-0 md:w-64',
        'hidden md:block'
      )}>
        <div className="h-full overflow-y-auto p-4">
          <h1 className="text-lg font-bold mb-6">Sistem TLHP</h1>
          <nav className="space-y-2">
            {menuItems.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={cn(
                  'block px-3 py-2 rounded text-sm',
                  location.pathname === item.path
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-300 hover:bg-gray-800'
                )}
              >
                {item.label}
              </Link>
            ))}
          </nav>
        </div>
      </aside>

      {/* Main */}
      <main className="flex-1 flex flex-col overflow-hidden">
        <header className="bg-white border-b px-4 py-3 flex items-center justify-between">
          <h2 className="text-lg font-semibold">Sistem TLHP</h2>
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="md:hidden text-gray-600"
          >
            Menu
          </button>
        </header>
        <div className="flex-1 overflow-y-auto p-6">
          {children || <Outlet />}
        </div>
      </main>
    </div>
  )
}

export { AppLayout }
