import { useHealth } from '@/hooks/useHealth'
import { Spinner } from '@/components/ui/spinner'
import { StatusBadge } from '@/components/ui/badge'

const HomePage = () => {
  const { data, isLoading, isError, error } = useHealth()

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">Dashboard</h1>

      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold mb-4">Status Sistem</h2>

        {isLoading && (
          <div className="flex items-center space-x-3">
            <Spinner />
            <span>Memeriksa status...</span>
          </div>
        )}

        {isError && (
          <div className="text-red-600">
            Error: {error?.message}
          </div>
        )}

        {data && (
          <div className="space-y-3">
            <p><span className="font-medium">API Status:</span> <StatusBadge status={data.data.status} /></p>
            <p><span className="font-medium">Database:</span> <StatusBadge status={data.data.database} /></p>
            <p><span className="font-medium">Aplikasi:</span> {data.data.app} v{data.data.version}</p>
            <p><span className="font-medium">Timestamp:</span> {data.data.timestamp}</p>
          </div>
        )}
      </div>
    </div>
  )
}

export default HomePage
