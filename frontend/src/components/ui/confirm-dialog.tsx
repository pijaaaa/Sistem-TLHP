interface ConfirmDialogProps {
  open: boolean
  title: string
  message?: string
  confirmLabel?: string
  cancelLabel?: string
  onConfirm: () => void
  onClose: () => void
  variant?: 'danger' | 'default'
}

const ConfirmDialog = ({
  open,
  title,
  message,
  confirmLabel = 'Konfirmasi',
  cancelLabel = 'Batal',
  onConfirm,
  onClose,
  variant = 'default',
}: ConfirmDialogProps) => {
  if (!open) return null

  const btnClass = variant === 'danger'
    ? 'bg-red-600 hover:bg-red-700'
    : 'bg-blue-600 hover:bg-blue-700'

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center">
      <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h2 className="text-lg font-semibold mb-2">{title}</h2>
        {message && <p className="text-gray-600 mb-4">{message}</p>}
        <div className="flex justify-end space-x-3">
          <button onClick={onClose} className="px-4 py-2 text-sm border border-gray-300 rounded">
            {cancelLabel}
          </button>
          <button onClick={onConfirm} className={`text-white px-4 py-2 text-sm rounded ${btnClass}`}>
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}

export { ConfirmDialog }
