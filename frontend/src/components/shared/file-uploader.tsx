import { useState, useRef } from 'react'
import { cn } from '@/lib/utils'

interface FileWithLabel {
  id: string
  file: File
  label: string
}

interface FileUploaderProps {
  onFilesChange?: (files: FileWithLabel[]) => void
  maxFiles?: number
  maxSizeMB?: number
  allowedTypes?: string[]
  labelPlaceholder?: string
  className?: string
}

const FILE_TYPE_LABELS: Record<string, string> = {
  audit: 'Audit',
  evidence: 'Evidence',
  'rekomendasi': 'Rekomendasi',
}

const FileUploader = ({
  onFilesChange,
  maxFiles = 10,
  maxSizeMB = 10,
  allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
  labelPlaceholder = 'Masukkan label...',
  className,
}: FileUploaderProps) => {
  const [files, setFiles] = useState<FileWithLabel[]>([])
  const [labels, setLabels] = useState<Record<string, string>>({})
  const inputRef = useRef<HTMLInputElement>(null)

  const updateFiles = (newFiles: FileWithLabel[]) => {
    setFiles(newFiles)
    onFilesChange?.(newFiles)
  }

  const handleSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selected = Array.from(e.target.files || [])
    const valid = selected.filter((f) => {
      const ext = f.name.split('.').pop()?.toLowerCase()
      const sizeMB = f.size / (1024 * 1024)
      return ext && allowedTypes.includes(ext) && sizeMB <= maxSizeMB
    })

    const newFiles = valid.map((f) => ({
      id: crypto.randomUUID(),
      file: f,
      label: '',
    }))
    updateFiles([...files, ...newFiles].slice(0, maxFiles))
    e.target.value = ''
  }

  const handleLabelChange = (id: string, label: string) => {
    setLabels((prev) => ({ ...prev, [id]: label }))
    const newFiles = files.map((f) =>
      f.id === id ? { ...f, label } : f
    )
    updateFiles(newFiles)
  }

  const removeFile = (id: string) => {
    const newFiles = files.filter((f) => f.id !== id)
    setLabels((prev) => {
      const copy = { ...prev }
      delete copy[id]
      return copy
    })
    updateFiles(newFiles)
  }

  return (
    <div className={cn('border-2 border-dashed border-gray-300 rounded-lg p-4', className)}>
      <input
        ref={inputRef}
        type="file"
        multiple
        accept={allowedTypes.map((t) => `.${t}`).join(',')}
        onChange={handleSelect}
        className="hidden"
      />
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className="text-blue-600 hover:text-blue-700 text-sm font-medium"
      >
        Pilih File
      </button>
      <p className="text-xs text-gray-500 mt-1">
        Format: {allowedTypes.join(', ')} | Max: {maxSizeMB}MB | Maks: {maxFiles} file
      </p>

      {files.length > 0 && (
        <div className="mt-4 space-y-3">
          {files.map((f) => (
            <div key={f.id} className="flex items-center space-x-3">
              <span className="text-sm text-gray-600 truncate">{f.file.name}</span>
              <input
                type="text"
                placeholder={labelPlaceholder}
                value={f.label || labels[f.id] || ''}
                onChange={(e) => handleLabelChange(f.id, e.target.value)}
                className="flex-1 px-2 py-1 text-sm border border-gray-300 rounded"
                list={`labels-${f.id}`}
              />
              <datalist id={`labels-${f.id}`}>
                {Object.values(FILE_TYPE_LABELS).map((l) => (
                  <option key={l} value={l} />
                ))}
              </datalist>
              <button
                onClick={() => removeFile(f.id)}
                className="text-red-600 hover:text-red-700 text-sm"
              >
                Hapus
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

export { FileUploader, type FileUploaderProps }
export type { FileWithLabel }
