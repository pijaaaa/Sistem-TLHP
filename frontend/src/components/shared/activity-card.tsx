import { FileText, Image, Video, File } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'

interface ActivityCardProps {
  title: string
  description?: string
  category: string
  categoryVariant?: 'default' | 'success' | 'warning' | 'danger' | 'info'
  date: string
  fileType?: 'pdf' | 'jpg' | 'png' | 'mp4' | 'doc' | string
  onClick?: () => void
}

const getFileIcon = (fileType?: string) => {
  if (!fileType) return File
  const type = fileType.toLowerCase()
  if (type.includes('pdf') || type.includes('doc')) return FileText
  if (type.includes('jpg') || type.includes('png') || type.includes('image')) return Image
  if (type.includes('mp4') || type.includes('video')) return Video
  return File
}

export const ActivityCard = ({
  title,
  description,
  category,
  categoryVariant = 'default',
  date,
  fileType,
  onClick,
}: ActivityCardProps) => {
  const FileIcon = getFileIcon(fileType)

  return (
    <div
      className={cn(
        'bg-white rounded-lg shadow-sm border border-gray-200 p-4 transition-all duration-200 hover:shadow-md',
        onClick && 'cursor-pointer hover:-translate-y-0.5'
      )}
      onClick={onClick}
    >
      <div className="flex items-start gap-3">
        <div className="rounded-full bg-gray-100 p-3 flex-shrink-0">
          <FileIcon size={24} className="text-gray-600" />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-2 mb-2">
            <h3 className="text-sm font-semibold text-gray-900 line-clamp-1">{title}</h3>
            <Badge variant={categoryVariant} className="flex-shrink-0">
              {category}
            </Badge>
          </div>
          {description && (
            <p className="text-xs text-gray-600 line-clamp-2 mb-2">{description}</p>
          )}
          <p className="text-xs text-gray-400">{date}</p>
        </div>
      </div>
    </div>
  )
}
