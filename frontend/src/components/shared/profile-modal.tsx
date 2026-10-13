import { useState } from 'react'
import { Modal, InputField, Button } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { useAuth } from '@/contexts/AuthContext'
import { apiClient } from '@/api/client'
import { isAxiosError } from 'axios'

interface ProfileModalProps {
  open: boolean
  onClose: () => void
  user: { name: string, username: string }
}

export const ProfileModal = ({ open, onClose, user }: ProfileModalProps) => {
  const { showToast } = useToast()
  const { refreshMe } = useAuth()
  const [form, setForm] = useState({ name: user.name, current_password: '', password: '', password_confirmation: '' })
  const [loading, setLoading] = useState(false)

  const submit = async () => {
    setLoading(true)
    try {
      if (form.name !== user.name) {
        await apiClient.put('/auth/profile', { name: form.name })
      }
      if (form.password) {
        await apiClient.post('/auth/change-password', { 
            current_password: form.current_password,
            password: form.password,
            password_confirmation: form.password_confirmation
        })
      }
      showToast('Profil berhasil diperbarui', 'success')
      await refreshMe()
      onClose()
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal menyimpan' : 'Gagal menyimpan', 'error')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal open={open} onClose={onClose} title="Edit Profil & Password">
      <div className="space-y-4">
        <InputField label="Nama Lengkap" value={form.name} onChange={(e) => setForm({...form, name: e.target.value})} />
        <hr className="my-4" />
        <InputField label="Password Saat Ini" type="password" value={form.current_password} onChange={(e) => setForm({...form, current_password: e.target.value})} />
        <InputField label="Password Baru" type="password" value={form.password} onChange={(e) => setForm({...form, password: e.target.value})} />
        <InputField label="Konfirmasi Password" type="password" value={form.password_confirmation} onChange={(e) => setForm({...form, password_confirmation: e.target.value})} />
        <div className="flex justify-end gap-2 pt-4">
          <Button variant="outline" onClick={onClose}>Batal</Button>
          <Button onClick={submit} disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan'}</Button>
        </div>
      </div>
    </Modal>
  )
}
