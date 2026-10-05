import { type ReactNode } from 'react'
import { cn } from '@/lib/utils'
import { Spinner } from '@/components/ui/spinner'
import { Can } from '@/components/shared'
import type { Action } from '@/types/auth'

export interface Column<T> {
  key: string
  header: string
  body: (row: T) => ReactNode
  sortable?: boolean
  className?: string
}

interface DataTableProps<T> {
  data: T[]
  columns: Column<T>[]
  loading?: boolean
  searchable?: boolean
  onSearch?: (value: string) => void
  searchPlaceholder?: string
  pagination?: {
    current: number
    perPage: number
    total: number
    onChange: (page: number) => void
  }
  emptyMessage?: string
  className?: string
  permissionMenu?: string
  permissionAction?: Action
}

const DataTable = <T extends { id?: unknown }>({
  data,
  columns,
  loading,
  searchable,
  onSearch,
  searchPlaceholder = 'Cari...',
  pagination,
  emptyMessage = 'Tidak ada data',
  className,
  permissionMenu,
  permissionAction = 'view',
}: DataTableProps<T>) => {
  if (permissionMenu) {
    return (
      <Can menu={permissionMenu} action={permissionAction} fallback={<></>}>
        <DataTableInner
          data={data}
          columns={columns}
          loading={loading}
          searchable={searchable}
          onSearch={onSearch}
          searchPlaceholder={searchPlaceholder}
          pagination={pagination}
          emptyMessage={emptyMessage}
          className={className}
        />
      </Can>
    )
  }

  return (
    <DataTableInner
      data={data}
      columns={columns}
      loading={loading}
      searchable={searchable}
      onSearch={onSearch}
      searchPlaceholder={searchPlaceholder}
      pagination={pagination}
      emptyMessage={emptyMessage}
      className={className}
    />
  )
}

const DataTableInner = <T extends { id?: unknown }>({
  data,
  columns,
  loading,
  searchable,
  onSearch,
  searchPlaceholder,
  pagination,
  emptyMessage,
  className,
}: Omit<DataTableProps<T>, 'permissionMenu' | 'permissionAction'>) => {
  return (
    <div className={cn('w-full overflow-x-auto', className)}>
      {searchable && onSearch && (
        <div className="mb-4">
          <input
            type="text"
            placeholder={searchPlaceholder}
            onChange={(e) => onSearch(e.target.value)}
            className="w-full md:w-64 px-3 py-2 border border-gray-300 rounded"
          />
        </div>
      )}

      <div className="min-w-[600px]">
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-50 border-b">
              {columns.map((col) => (
                <th key={col.key} className={cn('text-left px-4 py-2 text-sm font-medium text-gray-700', col.className)}>
                  {col.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={columns.length} className="text-center py-8">
                  <div className="flex items-center justify-center space-x-2">
                    <Spinner />
                    <span>Memuat...</span>
                  </div>
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan={columns.length} className="text-center py-8 text-gray-500">
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              data.map((row, i) => (
                <tr key={String(row.id ?? i)} className={i % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                  {columns.map((col) => (
                    <td key={col.key} className="px-4 py-2 text-sm">
                      {col.body(row)}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {pagination && (
        <div className="flex justify-between items-center mt-4 text-sm">
          <span>{pagination.total} data total</span>
          <div className="flex space-x-2">
            {Array.from({ length: Math.ceil(pagination.total / pagination.perPage) })
              .map((_, i) => i + 1)
              .slice(Math.max(0, pagination.current - 2), pagination.current + 3)
              .map((page) => (
                <button
                  key={page}
                  onClick={() => pagination.onChange(page)}
                  className={cn(
                    'px-3 py-1 rounded',
                    page === pagination.current
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-200 hover:bg-gray-300',
                  )}
                >
                  {page}
                </button>
              ))}
          </div>
        </div>
      )}
    </div>
  )
}

export { DataTable }
