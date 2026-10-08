import { useState } from 'react'
import { useFollowUpComments, useAddFollowUpComment } from '@/hooks/useFollowUps'
import { Button, Spinner, Textarea } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { isAxiosError } from 'axios'

interface CommentThreadProps {
  followUpId: number
  canComment?: boolean
  commentKind?: 'IA_COMMENT' | 'DISKUSI'
  placeholder?: string
}

const CommentThread = ({
  followUpId,
  canComment = false,
  commentKind = 'IA_COMMENT',
  placeholder = 'Tulis komentar...',
}: CommentThreadProps) => {
  const { showToast } = useToast()
  const { data, isLoading } = useFollowUpComments(followUpId)
  const add = useAddFollowUpComment()
  const [body, setBody] = useState('')

  const submit = async () => {
    if (!body.trim()) return
    try {
      await add.mutateAsync({ id: followUpId, kind: commentKind, body: body.trim() })
      showToast('Komentar ditambahkan.', 'success')
      setBody('')
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menambah komentar' : 'Gagal menambah komentar', 'error')
    }
  }

  return (
    <div className="space-y-3">
      {isLoading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat komentar...</div>
      ) : !data || data.length === 0 ? (
        <p className="text-sm text-gray-400">Belum ada komentar — dianggap setuju.</p>
      ) : (
        <ul className="space-y-2">
          {data.map((c) => (
            <li key={c.id} className="text-sm bg-gray-50 rounded p-3">
              <div className="flex items-center gap-2 mb-1">
                <span className="text-xs font-medium text-blue-700">{c.kind_label}</span>
                <span className="text-xs text-gray-500">{c.author?.name}</span>
                <span className="text-xs text-gray-400">{new Date(c.created_at).toLocaleString('id-ID')}</span>
              </div>
              <p className="text-gray-700 whitespace-pre-wrap">{c.body}</p>
            </li>
          ))}
        </ul>
      )}

      {canComment && (
        <div className="space-y-2">
          <Textarea rows={2} value={body} onChange={(e) => setBody(e.target.value)} placeholder={placeholder} />
          <div className="flex justify-end">
            <Button size="sm" onClick={submit} disabled={add.isPending || !body.trim()}>
              {add.isPending ? 'Mengirim...' : 'Kirim Komentar'}
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}

export { CommentThread }