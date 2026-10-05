import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'

export default function NotFound() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50">
      <div className="text-center">
        <h1 className="text-6xl font-bold text-gray-900">404</h1>
        <p className="text-lg text-gray-600 mt-2">Halaman tidak ditemukan.</p>
        <p className="text-sm text-gray-500 mb-6">Halaman yang Anda cari tidak tersedia.</p>
        <Link to="/dashboard">
          <Button>Kembali ke Beranda</Button>
        </Link>
      </div>
    </div>
  )
}
