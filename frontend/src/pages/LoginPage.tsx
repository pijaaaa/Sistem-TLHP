import { useState } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { motion } from 'framer-motion'

export default function LoginPage() {
  const [email, setEmail] = useState('admin_spi@example.com')
  const [password, setPassword] = useState('password')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const navigate = useNavigate()
  const location = useLocation()
  const { login } = useAuth()

  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/dashboard'

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      await login(email, password)
      navigate(from, { replace: true })
    } catch (err: any) {
      setError(err.response?.data?.message || 'Email atau password salah')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-50 via-white to-primary-50">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md"
      >
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
          {/* Header with brand */}
          <div className="bg-gradient-to-r from-primary-600 to-primary-700 p-8 text-center">
            <div className="flex justify-center mb-4">
              {/* Logo placeholder - user will add actual logo */}
              <div className="w-20 h-20 bg-white rounded-full flex items-center justify-center">
                <div className="w-16 h-16 bg-primary-600 rounded-full" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-white">Sistem TLHP</h1>
            <p className="text-sm text-primary-100 mt-1">Bumi Siak Pusako</p>
          </div>

          {/* Form section */}
          <div className="p-8">
            <div className="text-center mb-6">
              <h2 className="text-xl font-semibold text-gray-900">Login untuk melanjutkan</h2>
              <p className="text-sm text-gray-600 mt-1">Masukkan email dan password Anda</p>
            </div>

            {error && (
              <motion.div
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="p-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg mb-6"
              >
                {error}
              </motion.div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5">
              <div>
                <Label htmlFor="email" className="text-gray-700 font-medium">
                  Email
                </Label>
                <Input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  autoComplete="email"
                  placeholder="admin_spi@example.com"
                  className="mt-1"
                  required
                />
              </div>

              <div>
                <Label htmlFor="password" className="text-gray-700 font-medium">
                  Password
                </Label>
                <Input
                  id="password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoComplete="current-password"
                  placeholder="••••••••"
                  className="mt-1"
                  required
                />
              </div>

              <Button 
                type="submit" 
                className="w-full mt-6" 
                size="lg"
                disabled={loading}
              >
                {loading ? 'Masuk...' : 'Masuk'}
              </Button>
            </form>

            <p className="text-xs text-center text-gray-500 mt-6">
              © 2026 Sistem TLHP - Bumi Siak Pusako
            </p>
          </div>
        </div>
      </motion.div>
    </div>
  )
}
