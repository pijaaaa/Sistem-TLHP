import { useState } from 'react'
import { Button, Modal, FormField, Textarea, Input } from '@/components/ui'
import { WeightMeter } from '@/components/shared/WeightMeter'
import { useToast } from '@/components/ui/toast'
import {
  useApproveFollowUp,
  useRequestFollowUpRevision,
  useRejectFollowUp,
  useReturnFollowUpToRevision,
  useOverrideFollowUpWeight,
  useApproveCompletion,
  useCompletionRevision,
} from '@/hooks/useFollowUps'
import type { FollowUp } from '@/types/finding'
import { isAxiosError } from 'axios'

interface ReviewActionBarProps {
  followUp: FollowUp
  canDecide?: boolean
  canReturn?: boolean
}

type NoteAction = 'revision' | 'reject' | 'return' | 'completion' | null
type WeightAction = { open: boolean; weight: number }

const ReviewActionBar = ({ followUp, canDecide = false, canReturn = false }: ReviewActionBarProps) => {
  const { showToast } = useToast()
  const approve = useApproveFollowUp()
  const revision = useRequestFollowUpRevision()
  const reject = useRejectFollowUp()
  const ret = useReturnFollowUpToRevision()
  const override = useOverrideFollowUpWeight()
  const complete = useApproveCompletion()
  const completionRevision = useCompletionRevision()

  const [noteAction, setNoteAction] = useState<NoteAction>(null)
  const [note, setNote] = useState('')
  const [weight, setWeight] = useState<WeightAction>({ open: false, weight: followUp.weight })
  const [busy, setBusy] = useState(false)

  const err = (e: unknown) => showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal' : 'Gagal', 'error')

  const submitNote = async () => {
    if (!note.trim()) {
      showToast('Catatan wajib diisi.', 'error')
      return
    }
    setBusy(true)
    try {
      if (noteAction === 'revision') await revision.mutateAsync({ id: followUp.id, note: note.trim() })
      if (noteAction === 'reject') await reject.mutateAsync({ id: followUp.id, note: note.trim() })
      if (noteAction === 'return') await ret.mutateAsync({ id: followUp.id, note: note.trim() })
      if (noteAction === 'completion') await completionRevision.mutateAsync({ id: followUp.id, note: note.trim() })
      showToast('Keputusan berhasil disimpan.', 'success')
      setNoteAction(null)
      setNote('')
    } catch (e) {
      err(e)
    } finally {
      setBusy(false)
    }
  }

  const submitWeight = async () => {
    setBusy(true)
    try {
      await override.mutateAsync({ id: followUp.id, weight: weight.weight })
      showToast('Bobot diperbarui.', 'success')
      setWeight({ open: false, weight: followUp.weight })
    } catch (e) {
      err(e)
    } finally {
      setBusy(false)
    }
  }

  const busyAny = approve.isPending || revision.isPending || reject.isPending || ret.isPending || override.isPending || complete.isPending || completionRevision.isPending

  return (
    <div className="flex gap-2 flex-wrap">
      {canDecide && followUp.status === 'DIAJUKAN' && (
        <>
          <Button size="sm" onClick={() => approve.mutateAsync(followUp.id).catch(err)}>Setujui</Button>
          <Button size="sm" variant="outline" onClick={() => { setNoteAction('revision'); setNote('') }}>Revisi</Button>
          <Button size="sm" variant="destructive" onClick={() => { setNoteAction('reject'); setNote('') }}>Tolak</Button>
        </>
      )}

      <Button size="sm" variant="outline" onClick={() => { setWeight({ open: true, weight: followUp.weight }) }}>Ubah Bobot</Button>

      {canDecide && followUp.status === 'MENUNGGU_PERSETUJUAN_SELESAI' && (
        <>
          <Button size="sm" onClick={() => complete.mutateAsync(followUp.id).catch(err)}>Setujui Selesai</Button>
          <Button size="sm" variant="outline" onClick={() => { setNoteAction('completion'); setNote('') }}>Minta Revisi Penyelesaian</Button>
        </>
      )}

      {canReturn && followUp.status === 'DISETUJUI' && (
        <Button size="sm" variant="secondary" onClick={() => { setNoteAction('return'); setNote('') }}>Kembalikan ke Revisi</Button>
      )}

      <Modal open={noteAction !== null} onClose={() => setNoteAction(null)} title="Catatan Keputusan">
        <div className="space-y-4">
          <FormField label="Catatan *">
            <Textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} />
          </FormField>
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setNoteAction(null)}>Batal</Button>
            <Button onClick={submitNote} disabled={busy || busyAny}>Simpan</Button>
          </div>
        </div>
      </Modal>

      <Modal open={weight.open} onClose={() => setWeight((w) => ({ ...w, open: false }))} title="Ubah Bobot">
        <div className="space-y-4">
          <Input
            type="number"
            min={1}
            max={100}
            step={1}
            value={weight.weight}
            onChange={(e) => setWeight((w) => ({ ...w, weight: Number(e.target.value) }))}
          />
          <WeightMeter current={Number(weight.weight) || 0} />
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setWeight((w) => ({ ...w, open: false }))}>Batal</Button>
            <Button onClick={submitWeight} disabled={busy || busyAny}>Simpan</Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}

export { ReviewActionBar }