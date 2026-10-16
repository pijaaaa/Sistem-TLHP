import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Eye } from 'lucide-react'
import { useSpiQueue } from '@/hooks/useSpiReview'
import { PageHeader } from '@/components/shared'
import { StatusBadge, Spinner, Button } from '@/components/ui'

export default function SpiReviewQueuePage() {
  const { data, isLoading } = useSpiQueue()

  return (
    <div className="space-y-4">
      <PageHeader title="Review SPI" subtitle="Antrian action plan yang diajukan Admin SPI untuk ditinjau" />

      {isLoading ? (
        <div className="flex items-center gap-2 text-sm text-gray-500"><Spinner /> Memuat...</div>
      ) : !data || data.data.length === 0 ? (
        <p className="text-sm text-gray-500">Belum ada action plan yang diajukan ke Admin SPI.</p>
      ) : (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-200">
                  <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Aksi</th>
                  <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Kode</th>
                  <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Judul</th>
                  <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Temuan</th>
                  <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Departemen</th>
                  <th className="text-left px-6 py-3 text-xs font-semibold text-gray-700">Risiko</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {data.data.map((ap) => (
                  <tr key={ap.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4">
                      <Link to={`/review-spi/${ap.id}`}>
                        <Button size="sm" variant="primary" className="flex items-center gap-2">
                          <Eye className="w-4 h-4" />
                          Tinjau
                        </Button>
                      </Link>
                    </td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{ap.code}</td>
                    <td className="px-6 py-4 text-sm text-gray-700">{ap.title}</td>
                    <td className="px-6 py-4 text-sm text-gray-700">{ap.finding?.registration_number ?? '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-700">{ap.department?.name ?? '-'}</td>
                    <td className="px-6 py-4"><StatusBadge status={ap.risk_label ?? '-'} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}