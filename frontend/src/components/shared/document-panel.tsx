import { useState } from 'react'
import type { DocumentFile } from '@/types/finding'
import { Button, Input, FormField, Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { confirmDownload } from '@/lib/utils'

interface DocumentPanelProps {
  documents?: DocumentFile[]
  loading?: boolean
  canManage?: boolean
  download: (docId: number) => string
  onUpload: (file: File, label: string) => Promise<void>
  onDelete?: (docId: number) => Promise<void>
}

const formatSize = (bytes: number) => {
  if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  return `${Math.max(1, Math.round(bytes / 1024))} KB`
}

const DocumentPanel = ({ documents, loading, canManage, download, onUpload, onDelete }: DocumentPanelProps) => {
  const { showToast } = useToast()
  const [file, setFile] = useState<File | null>(null)
  const [label, setLabel] = useState('')
  const [uploading, setUploading] = useState(false)

  const submit = async () => {
    if (!file || !label.trim()) {
      showToast('Pilih file dan isi label dokumen.', 'error')
      return
    }
    try {
      setUploading(true)
      await onUpload(file, label.trim())
      showToast('Dokumen berhasil diunggah.', 'success')
      setFile(null)
      setLabel('')
    } catch {
      showToast('Gagal mengunggah dokumen.', 'error')
    } finally {
      setUploading(false)
    }
  }

  return (
    <div className="space-y-4">
      {canManage && (
        <div className="bg-gray-50 border border-gray-200 rounded p-4 space-y-3">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <FormField label="Label *">
              <Input value={label} onChange={(e) => setLabel(e.target.value)} placeholder="contoh: LHP, Bukti Perbaikan" />
            </FormField>
            <FormField label="File *">
              <Input
                type="file"
                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
              />
            </FormField>
          </div>
          <div className="flex items-center gap-3">
            <Button onClick={submit} disabled={uploading}>{uploading ? 'Mengunggah...' : 'Unggah'}</Button>
            {file && <span className="text-sm text-gray-500 truncate">{file.name}</span>}
          </div>
        </div>
      )}

      {loading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat dokumen...</div>
      ) : !documents || documents.length === 0 ? (
        <p className="text-sm text-gray-500">Belum ada dokumen.</p>
      ) : (
        <ul className="divide-y divide-gray-100">
          {documents.map((d) => (
            <li key={d.id} className="flex items-center justify-between py-2">
              <div className="min-w-0">
                <p className="text-sm font-medium text-gray-800 truncate">{d.name}</p>
                <p className="text-xs text-gray-500">{d.label} · {formatSize(d.size)}</p>
              </div>
              <div className="flex gap-2 shrink-0">
                <Button size="sm" variant="outline" onClick={() => confirmDownload(download(d.id))}>Unduh</Button>
                {canManage && onDelete && (
                  <Button size="sm" variant="destructive" onClick={() => onDelete(d.id)}>Hapus</Button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

export { DocumentPanel }