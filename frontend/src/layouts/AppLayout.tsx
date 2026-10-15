import { useState } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { useAuth } from '@/contexts/AuthContext'
import type { MenuItem } from '@/types/auth'
import { Button } from '@/components/ui/button'
import { NotificationBell } from '@/components/shared/notification-bell'
import { AvatarCircle } from '@/components/shared/avatar-circle'
import { ProfileModal } from '@/components/shared/profile-modal'
import * as LucideIcons from 'lucide-react'

// Workflow group configuration
const WORKFLOW_GROUP = {
  name: 'Alur Kerja TLHP',
  icon: 'clipboard-list',
  codes: ['findings', 'action_plans', 'follow_ups']
}

const APPROVAL_GROUP = {
  name: 'Approval',
  icon: 'check-circle',
  codes: ['follow_up_reviews', 'ia_monitoring', 'spi_review', 'external_status']
}

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

const renderMenuTree = (
  menus: MenuItem[],
  location: { pathname: string },
  openDropdowns: Set<string>,
  toggleDropdown: (key: string) => void,
) => {
  const list: React.ReactNode[] = []

  // Ensure openDropdowns is a Set
  if (!openDropdowns || typeof openDropdowns.has !== 'function') {
    console.error('openDropdowns is not a Set:', openDropdowns)
    return list
  }

  // Filter out the 'exports' menu entirely from the render logic
  const filteredMenus = menus.filter(m => m.code !== 'exports')

  // Separate menus by group
  const dashboardMenu = filteredMenus.find(m => m.code === 'dashboard')
  const workflowMenus = filteredMenus.filter(m => WORKFLOW_GROUP.codes.includes(m.code))
  const approvalMenus = filteredMenus.filter(m => APPROVAL_GROUP.codes.includes(m.code))
  const otherMenus = filteredMenus.filter(m => 
    m.code !== 'dashboard' && 
    !WORKFLOW_GROUP.codes.includes(m.code) && 
    !APPROVAL_GROUP.codes.includes(m.code)
  )

  // Render dashboard first
  if (dashboardMenu) {
    const isActive = location.pathname === dashboardMenu.path
    const IconComponent = getIcon(dashboardMenu.icon)
    list.push(
      <div key={dashboardMenu.code}>
        <Link
          to={dashboardMenu.path}
          className={cn(
            'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
            isActive
              ? 'bg-accent-orange text-white'
              : 'text-gray-300 hover:bg-[#164436] hover:text-white',
          )}
        >
          <IconComponent size={18} className="mr-3" />
          {dashboardMenu.name}
        </Link>
      </div>,
    )
  }

  // Add workflow dropdown if there are workflow menus
  if (workflowMenus.length > 0) {
    const isOpen = openDropdowns.has('workflow')
    const hasActiveChild = workflowMenus.some(m => location.pathname === m.path)
    const WorkflowIcon = getIcon(WORKFLOW_GROUP.icon)
    const ChevronIcon = isOpen ? LucideIcons.ChevronDown : LucideIcons.ChevronRight

    list.push(
      <div key="workflow">
        <button
          onClick={() => toggleDropdown('workflow')}
          className={cn(
            'w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
            hasActiveChild
              ? 'bg-[#164436] text-white'
              : 'text-gray-300 hover:bg-[#164436] hover:text-white',
          )}
        >
          <div className="flex items-center">
            <WorkflowIcon size={18} className="mr-3" />
            {WORKFLOW_GROUP.name}
          </div>
          <ChevronIcon size={16} />
        </button>

        {isOpen && (
          <div className="ml-4 mt-1 space-y-1">
            {workflowMenus.map((menu) => {
              const isActive = location.pathname === menu.path
              const IconComponent = getIcon(menu.icon)
              return (
                <Link
                  key={menu.code}
                  to={menu.path}
                  className={cn(
                    'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
                    isActive
                      ? 'bg-accent-orange text-white'
                      : 'text-gray-300 hover:bg-[#164436] hover:text-white',
                  )}
                >
                  <IconComponent size={16} className="mr-3" />
                  {menu.name}
                </Link>
              )
            })}
          </div>
        )}
      </div>,
    )
  }

  // Add approval dropdown if there are approval menus
  if (approvalMenus.length > 0) {
    const isOpen = openDropdowns.has('approval')
    const hasActiveChild = approvalMenus.some(m => location.pathname === m.path)
    const ApprovalIcon = getIcon(APPROVAL_GROUP.icon)
    const ChevronIcon = isOpen ? LucideIcons.ChevronDown : LucideIcons.ChevronRight

    list.push(
      <div key="approval">
        <button
          onClick={() => toggleDropdown('approval')}
          className={cn(
            'w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
            hasActiveChild
              ? 'bg-[#164436] text-white'
              : 'text-gray-300 hover:bg-[#164436] hover:text-white',
          )}
        >
          <div className="flex items-center">
            <ApprovalIcon size={18} className="mr-3" />
            {APPROVAL_GROUP.name}
          </div>
          <ChevronIcon size={16} />
        </button>

        {isOpen && (
          <div className="ml-4 mt-1 space-y-1">
            {approvalMenus.map((menu) => {
              const isActive = location.pathname === menu.path
              const IconComponent = getIcon(menu.icon)
              return (
                <Link
                  key={menu.code}
                  to={menu.path}
                  className={cn(
                    'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
                    isActive
                      ? 'bg-accent-orange text-white'
                      : 'text-gray-300 hover:bg-[#164436] hover:text-white',
                  )}
                >
                  <IconComponent size={16} className="mr-3" />
                  {menu.name}
                </Link>
              )
            })}
          </div>
        )}
      </div>,
    )
  }

  // Render other menus
  otherMenus.forEach((menu) => {
    const isActive = location.pathname === menu.path
    const hasChildren = menu.children && menu.children.length > 0
    const IconComponent = getIcon(menu.icon)

    if (hasChildren) {
      const isOpen = openDropdowns.has(menu.code)
      const hasActiveChild = menu.children!.some(c => location.pathname === c.path)
      const ChevronIcon = isOpen ? LucideIcons.ChevronDown : LucideIcons.ChevronRight

      list.push(
        <div key={menu.code}>
          <button
            onClick={() => toggleDropdown(menu.code)}
            className={cn(
              'w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
              hasActiveChild
                ? 'bg-[#164436] text-white'
                : 'text-gray-300 hover:bg-[#164436] hover:text-white',
            )}
          >
            <div className="flex items-center">
              <IconComponent size={18} className="mr-3" />
              {menu.name}
            </div>
            <ChevronIcon size={16} />
          </button>

          {isOpen && (
            <div className="ml-4 mt-1 space-y-1">
              {menu.children!.map((child) => {
                const childActive = location.pathname === child.path
                const ChildIconComponent = getIcon(child.icon)
                return (
                  <Link
                    key={child.code}
                    to={child.path}
                    className={cn(
                      'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
                      childActive
                        ? 'bg-accent-orange text-white'
                        : 'text-gray-300 hover:bg-[#164436] hover:text-white',
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
    } else {
      list.push(
        <div key={menu.code}>
          {menu.path ? (
            <Link
              to={menu.path}
              className={cn(
                'flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
                isActive
                  ? 'bg-accent-orange text-white'
                  : 'text-gray-300 hover:bg-[#164436] hover:text-white',
              )}
            >
              <IconComponent size={18} className="mr-3" />
              {menu.name}
            </Link>
          ) : (
            <div className="flex items-center px-3 py-2 text-sm font-medium text-gray-500 uppercase tracking-wide">
              <IconComponent size={18} className="mr-3" />
              {menu.name}
            </div>
          )}
        </div>,
      )
    }
  })

  return list
}

const AppLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [openDropdowns, setOpenDropdowns] = useState<Set<string>>(new Set(['workflow']))
  const [profileOpen, setProfileOpen] = useState(false)
  const location = useLocation()
  const { menus, logout, user } = useAuth()

  const toggleDropdown = (key: string) => {
    setOpenDropdowns(prev => {
      const next = new Set(prev)
      if (next.has(key)) {
        next.delete(key)
      } else {
        next.add(key)
      }
      return next
    })
  }

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
          'fixed md:fixed top-0 left-0 h-screen bg-[#1a4d3c] text-white transition-transform duration-300 z-30 flex flex-col',
          sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0 md:w-64',
        )}
      >
        {/* Scrollable content */}
        <div className="flex-1 overflow-y-auto p-4 flex flex-col">
          <div className="flex flex-col items-center mb-6 px-2 py-4">
            <img 
              src="/skkmigas_bsp.png" 
              alt="SKKMIGAS & PT. Bumi Siak Pusako" 
              className="w-full max-w-[200px] h-auto mb-3"
            />
            <div className="text-center">
              <h1 className="text-white font-bold text-base leading-tight">e-TLHT</h1>
              <p className="text-gray-300 text-xs mt-1">Sistem Tindak Lanjut Hasil Temuan</p>
            </div>
          </div>
          
          {user && (
            <button 
              onClick={() => setProfileOpen(true)}
              className="mb-6 px-2 py-3 bg-[#164436] rounded-xl flex items-center gap-3 hover:bg-[#1a4d3c] transition-colors w-full text-left group"
            >
              <AvatarCircle name={user.name} size="md" className="group-hover:ring-2 group-hover:ring-accent-orange transition-all" />
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-white truncate">{user.name}</p>
                <p className="text-xs text-accent-yellow">{user.role}</p>
              </div>
            </button>
          )}
          
          <nav className="space-y-1 flex-1">
            {renderMenuTree(menus, location, openDropdowns, toggleDropdown)}
          </nav>
        </div>

        {/* Fixed Logout Button at Bottom */}
        <div className="p-4 border-t border-[#164436] bg-[#1a4d3c]">
          <Button
            variant="ghost"
            onClick={() => logout()}
            className="w-full text-left justify-start text-red-400 hover:bg-[#164436] hover:text-red-300 transition-colors"
          >
            <LucideIcons.LogOut size={18} className="mr-3" />
            Keluar
          </Button>
        </div>
      </aside>

      {/* Profile Modal */}
      {user && <ProfileModal open={profileOpen} onClose={() => setProfileOpen(false)} user={user} />}

      {/* Main */}
      <main className="flex-1 flex flex-col overflow-hidden md:ml-64">
        <header className="bg-white border-b px-4 py-3 flex items-center justify-between shadow-sm">
          <h2 className="text-lg font-semibold text-gray-800">Sistem TLHP</h2>
          <div className="flex items-center gap-2">
            <NotificationBell />
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="md:hidden text-gray-600"
            >
              ≈
            </button>
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
