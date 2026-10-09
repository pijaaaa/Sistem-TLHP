import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell } from 'lucide-react'
import { useUnreadCount, useNotifications, useMarkNotificationRead, useMarkAllNotificationsRead } from '@/hooks/useNotifications'
import { subjectRoute } from '@/lib/links'
import { cn } from '@/lib/utils'

const NotificationBell = () => {
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const { data: unread } = useUnreadCount()
  const { data: notifications } = useNotifications()
  const markRead = useMarkNotificationRead()
  const markAll = useMarkAllNotificationsRead()

  const openNotification = async (n: { id: string; data?: { url?: string | null } }) => {
    await markRead.mutateAsync(n.id)
    if (n.data?.url) {
      navigate(n.data.url)
    }
    setOpen(false)
  }

  return (
    <div className="relative">
          <button
        onClick={() => setOpen((o) => !o)}
        className="relative p-2.5 rounded-xl hover:bg-gray-100 text-gray-600 transition-colors"
        aria-label="Notifikasi"
      >
        <Bell size={20} />
        {(unread ?? 0) > 0 && (
          <span className="absolute -top-1 -right-1 bg-accent-500 text-white text-[10px] font-bold rounded-full min-w-5 h-5 px-1 flex items-center justify-center shadow-sm">
            {Math.min(unread ?? 0, 99)}
          </span>
        )}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute right-0 z-50 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div className="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
              <span className="text-sm font-bold text-gray-900">Notifikasi</span>
              {(unread ?? 0) > 0 && (
                <button onClick={() => markAll.mutateAsync()} className="text-xs font-semibold text-primary-700 hover:text-primary-800">
                  Tandai semua dibaca
                </button>
              )}
            </div>
            <div className="max-h-80 overflow-y-auto divide-y divide-gray-50">
              {!notifications || notifications.length === 0 ? (
                <p className="px-4 py-8 text-sm text-center text-gray-400">Tidak ada notifikasi.</p>
              ) : (
                notifications.map((n) => (
                  <button
                    key={n.id}
                    onClick={() => openNotification(n)}
                    className={cn(
                      'w-full text-left px-4 py-3 hover:bg-primary-50/50 transition-colors',
                      !n.read_at && 'bg-accent-50/40',
                    )}
                  >
                    <p className="text-sm font-medium text-gray-800">{n.title}</p>
                    <p className="text-xs text-gray-400 mt-1">
                      {new Date(n.created_at).toLocaleString('id-ID')}
                    </p>
                  </button>
                ))
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}

export { NotificationBell }