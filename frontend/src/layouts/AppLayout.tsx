import { useState } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { useAuth } from '@/contexts/AuthContext'
import type { MenuItem } from '@/types/auth'
import { Button } from '@/components/ui/button'
import { NotificationBell } from '@/components/shared/notification-bell'
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
    'globe': LucideIcons.Globe,
    'inbox': LucideIcons.Inbox,
    'bar-chart': LucideIcons.BarChart3,
    'logout': LucideIcons.LogOut,
    'settings': LucideIcons.Settings,
    'history': LucideIcons.History,
  }
  return iconMap[iconName] || LucideIcons.FileQuestion
}

const MenuGroup = ({ 
  menu, 
  location 
}: { 
  menu: MenuItem; 
  location: { pathname: string } 
}) => {
  const [isOpen, setIsOpen] = useState(false)
  const hasChildren = menu.children && menu.children.length > 0
  const IconComponent = getIcon(menu.icon)
  const isActive = location.pathname === menu.path || (hasChildren && menu.children?.some(c => location.pathname === c.path))

  if (!hasChildren) {
    return (
      <Link
        to={menu.path}
        className={cn(
          'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors',
          isActive
            ? 'bg-sidebar-active text-white'
            : 'text-gray-200 hover:bg-sidebar-hover hover:text-white',
        )}
      >
        <IconComponent size={18} className="mr-3" />
        {menu.name}
      </Link>
    )
  }

  return (
    <div>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className={cn(
          'w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-colors',
          isActive
            ? 'bg-sidebar-active text-white'
            : 'text-gray-200 hover:bg-sidebar-hover hover:text-white',
        )}
      >
        <div className="flex items-center">
          <IconComponent size={18} className="mr-3" />
          {menu.name}
        </div>
        <LucideIcons.ChevronDown 
          size={16} 
          className={cn('transition-transform', isOpen && 'rotate-180')} 
        />
      </button>
      {isOpen && (
        <div className="ml-6 mt-1 space-y-1">
          {menu.children!.map((child) => {
            const childActive = location.pathname === child.path
            const ChildIconComponent = getIcon(child.icon)
            return (
              <Link
                key={child.code}
                to={child.path}
                className={cn(
                  'flex items-center px-3 py-2 rounded-md text-sm transition-colors',
                  childActive
                    ? 'bg-sidebar-active text-white'
                    : 'text-gray-200 hover:bg-sidebar-hover hover:text-white',
                )}
              >
                <ChildIconComponent size={16} className="mr-3" />
                {child.name}
              </Link>
            )
          })}
        </div>
      )}
    </div>
  )
}

const renderMenuTree = (
  menus: MenuItem[],
  location: { pathname: string },
  logout: () => Promise<void>,
) => {
  const list: React.ReactNode[] = []

  menus.forEach((menu) => {
    list.push(<MenuGroup key={menu.code} menu={menu} location={location} />)
  })

  const LogoutIcon = LucideIcons.LogOut
  list.push(
    <Button
      key="logout"
      variant="ghost"
      onClick={() => logout()}
      className="w-full mt-4 text-left justify-start text-gray-200 hover:bg-sidebar-hover hover:text-white"
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
          'fixed md:fixed top-0 left-0 h-screen bg-sidebar text-white transition-transform duration-200 z-30',
          sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0 md:w-64',
        )}
      >
        <div className="h-full overflow-y-auto p-4 flex flex-col">
          <div className="mb-8">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 rounded-full bg-yellow-500 flex items-center justify-center font-bold text-sidebar text-lg">
                T
              </div>
              <div>
                <h1 className="text-lg font-bold">e-TLHT</h1>
                <p className="text-xs text-gray-300">Sistem TLHP</p>
              </div>
            </div>
            {user && (
              <div className="bg-sidebar-hover rounded-lg p-3 text-sm">
                <div className="font-medium truncate">{user.name}</div>
                <div className="text-xs text-gray-300">{user.email}</div>
              </div>
            )}
          </div>
          <nav className="space-y-1 flex-1">
            {renderMenuTree(menus, location, logout)}
          </nav>
        </div>
      </aside>

      {/* Main */}
      <main className="flex-1 flex flex-col overflow-hidden md:ml-64">
        <header className="bg-white border-b px-6 py-4 flex items-center justify-between shadow-sm">
          <div>
            <h2 className="text-xl font-semibold text-gray-800">Sistem e-TLHT</h2>
            <p className="text-sm text-gray-600">Selamat datang, {user?.name}</p>
          </div>
          <div className="flex items-center gap-4">
            <NotificationBell />
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="md:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-600"
            >
              <LucideIcons.Menu size={24} />
            </button>
            <div className="hidden md:flex items-center gap-2 bg-accent-500 text-white px-4 py-2 rounded-lg">
              <span className="text-sm font-medium">{user?.name}</span>
              <div className="w-8 h-8 rounded-full bg-white text-accent-500 flex items-center justify-center font-bold text-sm">
                {user?.name?.charAt(0).toUpperCase()}
              </div>
            </div>
          </div>
        </header>
        <div className="flex-1 overflow-y-auto p-6 bg-gray-50">
          <Outlet />
        </div>
      </main>
    </div>
  )
}

export { AppLayout }
