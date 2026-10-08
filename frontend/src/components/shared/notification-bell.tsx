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
        className="relative p-2 rounded-md hover:bg-gray-100 text-gray-600"
        aria-label="Notifikasi"
      >
        <Bell size={18} />
        {(unread ?? 0) > 0 && (
          <span className="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] rounded-full min-w-4 h-4 px-1 flex items-center justify-center">
            {Math.min(unread ?? 0, 99)}
          </span>
        )}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute right-0 z-50 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
            <div className="flex items-center justify-between px-4 py-2 border-b">
              <span className="text-sm font-semibold">Notifikasi</span>
              {(unread ?? 0) > 0 && (
                <button onClick={() => markAll.mutateAsync()} className="text-xs text-blue-600 hover:underline">
                  Tandai semua dibaca
                </button>
              )}
            </div>
            <div className="max-h-80 overflow-y-auto">
              {!notifications || notifications.length === 0 ? (
                <p className="px-4 py-6 text-sm text-gray-500">Tidak ada notifikasi.</p>
              ) : (
                notifications.map((n) => (
                  <button
                    key={n.id}
                    onClick={() => openNotification(n)}
                    className={cn(
                      'w-full text-left px-4 py-3 border-b hover:bg-gray-50',
                      !n.read_at && 'bg-blue-50/60',
                    )}
                  >
                    <p className="text-sm text-gray-800">{n.title}</p>
                    <p className="text-xs text-gray-400 mt-0.5">
                      {n.data?.url ? 'Buka subjek' : ''} · {new Date(n.created_at).toLocaleString('id-ID')}
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