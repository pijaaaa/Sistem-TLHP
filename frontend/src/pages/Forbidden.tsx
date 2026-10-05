import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'

export default function Forbidden() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50">
      <div className="text-center">
        <h1 className="text-6xl font-bold text-red-600">403</h1>
        <p className="text-lg text-gray-600 mt-2">Akses ditolak.</p>
        <p className="text-sm text-gray-500 mb-6">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <Link to="/dashboard">
          <Button>Kembali ke Beranda</Button>
        </Link>
      </div>
    </div>
  )
}
