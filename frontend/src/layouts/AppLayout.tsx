import { useState } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { useAuth } from '@/contexts/AuthContext'
import type { MenuItem } from '@/types/auth'
import { Button } from '@/components/ui/button'
import * as LucideIcons from 'lucide-react'

// Icon mapper from backend icon names to Lucide components
const getIcon = (iconName: string) => {
  const iconMap: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
    'layout-dashboard': LucideIcons.LayoutDashboard,
    'building': LucideIcons.Building2,
    'users': LucideIcons.Users,
    'shield': LucideIcons.Shield,
    'clipboard-list': LucideIcons.ClipboardList,
    'folder': LucideIcons.Folder,
    'send': LucideIcons.Send,
    'file-text': LucideIcons.FileText,
    'clipboard-edit': LucideIcons.ClipboardEdit,
    'clipboard-check': LucideIcons.ClipboardCheck,
    'upload': LucideIcons.Upload,
    'scale': LucideIcons.Scale,
    'search': LucideIcons.Search,
    'download': LucideIcons.Download,
    'check-circle': LucideIcons.CheckCircle2,
    'thumbs-up': LucideIcons.ThumbsUp,
    'eye': LucideIcons.Eye,
    'logout': LucideIcons.LogOut,
  }
  return iconMap[iconName] || LucideIcons.FileQuestion
}

const renderMenuTree = (
  menus: MenuItem[],
  location: { pathname: string },
  logout: () => Promise<void>,
) => {
  const list: React.ReactNode[] = []

  menus.forEach((menu) => {
    const isActive = location.pathname === menu.path
    const hasChildren = menu.children && menu.children.length > 0
    const IconComponent = getIcon(menu.icon)

    list.push(
      <div key={menu.code}>
        <Link
          to={menu.path}
          className={cn(
            'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
            isActive
              ? 'bg-blue-600 text-white'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white',
          )}
        >
          <IconComponent size={18} className="mr-3" />
          {menu.name}
        </Link>

        {hasChildren && (
          <div className="ml-6 mt-1 space-y-1">
            {menu.children!.map((child) => {
              const childActive = location.pathname === child.path
              const ChildIconComponent = getIcon(child.icon)
              return (
                <Link
                  key={child.code}
                  to={child.path}
                  className={cn(
                    'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
                    childActive
                      ? 'bg-blue-600 text-white'
                      : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                  )}
                >
                  <ChildIconComponent size={16} className="mr-3" />
                  {child.name}
                </Link>
              )
            })}
          </div>
        )}
      </div>,
    )
  })

  const LogoutIcon = LucideIcons.LogOut
  list.push(
    <Button
      key="logout"
      variant="ghost"
      onClick={() => logout()}
      className="w-full mt-4 text-left justify-start text-red-400 hover:bg-gray-800 hover:text-red-300"
    >
      <LogoutIcon size={18} className="mr-3" />
      Keluar
    </Button>,
  )

  return list
}

const AppLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()
  const { menus, logout, user } = useAuth()

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/50 md:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          'fixed md:fixed top-0 left-0 h-screen bg-gray-900 text-white transition-transform duration-200 z-30',
          sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0 md:w-64',
        )}
      >
        <div className="h-full overflow-y-auto p-4">
          <h1 className="text-xl font-bold mb-6">Sistem TLHP</h1>
          {user && (
            <div className="mb-2 text-xs text-gray-500">
              {user.name} — {user.role}
            </div>
          )}
          <nav className="space-y-1">
            {renderMenuTree(menus, location, logout)}
          </nav>
        </div>
      </aside>

      {/* Main */}
      <main className="flex-1 flex flex-col overflow-hidden md:ml-64">
        <header className="bg-white border-b px-4 py-3 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-800">Sistem TLHP</h2>
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="md:hidden text-gray-600"
          >
            ☰
          </button>
        </header>
        <div className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </div>
      </main>
    </div>
  )
}

export { AppLayout }
