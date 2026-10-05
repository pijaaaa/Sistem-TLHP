import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Modal } from '@/components/ui/modal'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { StatusBadge } from '@/components/ui/badge'
import { FileUploader } from '@/components/shared/file-uploader'
import { PageHeader } from '@/components/shared/page-header'
import { DataTable } from '@/components/shared/data-table'
import { useToast } from '@/components/ui/toast'

const sampleData = [
  { id: 1, name: 'Temuan A', status: 'draft' },
  { id: 2, name: 'Temuan B', status: 'approved' },
  { id: 3, name: 'Temuan C', status: 'process' },
]

const columns = [
  { key: 'name', header: 'Nama', body: (row: any) => <span>{row.name}</span> },
  { key: 'status', header: 'Status', body: (row: any) => <StatusBadge status={row.status} /> },
  { key: 'action', header: 'Aksi', body: () => <Button size="sm" variant="outline">Lihat</Button> },
]

const DevComponentsPage = () => {
  const [modalOpen, setModalOpen] = useState(false)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [search, setSearch] = useState('')
  const [filteredData] = useState(sampleData.filter((d) =>
    d.name.toLowerCase().includes(search.toLowerCase())
  ))

  const { showToast } = useToast()

  return (
    <div className="p-6">
      <PageHeader
        title="Dev Components"
        subtitle="Halaman demo komponen reusable"
        action={
          <Button variant="outline" onClick={() => showToast('Ini toast notifikasi!', 'info')}>
            Tampilkan Toast
          </Button>
        }
      />

      <div className="space-y-6">
        {/* Button & Input */
        }
        <section>
          <h3 className="text-md font-semibold mb-3">Button & Input</h3>
          <div className="flex flex-wrap gap-3 items-end">
            <Button variant="primary">Primary</Button>
            <Button variant="secondary">Secondary</Button>
            <Button variant="outline">Outline</Button>
            <Button variant="destructive">Hapus</Button>
            <Input placeholder="Contoh input..." className="w-48" />
            <Select className="w-48">
              <option value="">Pilih status</option>
              <option value="draft">Draft</option>
              <option value="approved">Approved</option>
            </Select>
          </div>
        </section>

        {/* Textarea */
        }
        <section>
          <h3 className="text-md font-semibold mb-3">Textarea</h3>
          <Textarea placeholder="Masukkan deskripsi..." className="w-full max-w-2xl" />
        </section>

        {/* Modal & ConfirmDialog */
        }
        <section>
          <h3 className="text-md font-semibold mb-3">Modal & Dialog</h3>
          <div className="flex gap-3">
            <Button onClick={() => setModalOpen(true)}>Buka Modal</Button>
            <Button variant="outline" onClick={() => setConfirmOpen(true)}>Buka Confirm Dialog</Button>
          </div>
        </section>

        {/* FileUploader */
        }
        <section>
          <h3 className="text-md font-semibold mb-3">FileUploader</h3>
          <FileUploader
            onFilesChange={(files) => console.log('Files:', files)}
            maxFiles={5}
            maxSizeMB={2}
          />
        </section>

        {/* DataTable */
        }
        <section>
          <h3 className="text-md font-semibold mb-3">DataTable</h3>
          <DataTable
            data={filteredData}
            columns={columns}
            searchable
            searchPlaceholder="Cari temuan..."
            onSearch={setSearch}
            pagination={{ current: 1, perPage: 10, total: sampleData.length, onChange: () => {} }}
            emptyMessage="Tidak ada temuan ditemukan"
          />
        </section>
      </div>

      {/* Modals */
      }
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Contoh Modal">
        <p>Ini adalah konten modal contoh. Gunakan untuk form atau konfirmasi.</p>
        <div className="flex justify-end mt-4">
          <Button variant="outline" onClick={() => setModalOpen(false)}>
            Tutup
          </Button>
        </div>
      </Modal>

      <ConfirmDialog
        open={confirmOpen}
        title="Hapus data?"
        message="Data yang sudah dihapus tidak dapat dikembalikan."
        variant="danger"
        onConfirm={() => {
          showToast('Data berhasil dihapus!', 'success')
          setConfirmOpen(false)
        }}
        onClose={() => setConfirmOpen(false)}
      />
    </div>
  )
}

export default DevComponentsPage
