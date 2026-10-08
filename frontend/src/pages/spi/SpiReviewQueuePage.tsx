import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useSpiQueue } from '@/hooks/useSpiReview'
import { PageHeader } from '@/components/shared'
import { StatusBadge, Spinner } from '@/components/ui'

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
        <div className="bg-white rounded-lg shadow overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50">
                <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Kode</th>
                <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Judul</th>
                <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Temuan</th>
                <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Departemen</th>
                <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Risiko</th>
                <th className="text-left px-4 py-2 text-xs font-medium text-gray-600">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {data.data.map((ap) => (
                <tr key={ap.id} className="border-t">
                  <td className="px-4 py-2 text-sm font-medium">{ap.code}</td>
                  <td className="px-4 py-2 text-sm">{ap.title}</td>
                  <td className="px-4 py-2 text-sm">{ap.finding?.registration_number ?? '-'}</td>
                  <td className="px-4 py-2 text-sm">{ap.department?.name ?? '-'}</td>
                  <td className="px-4 py-2"><StatusBadge status={ap.risk_label ?? '-'} /></td>
                  <td className="px-4 py-2">
                    <Link to={`/review-spi/${ap.id}`} className="text-blue-600 hover:underline text-sm">
                      Tinjau →
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}