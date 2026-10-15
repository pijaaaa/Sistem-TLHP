import { useState, useEffect } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'
import { useAuth } from '@/contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { motion, AnimatePresence } from 'framer-motion'
import { User, Lock, RefreshCw, AlertTriangle, CheckCircle, Eye, EyeOff } from 'lucide-react'
import { CaptchaCanvas } from '@/components/shared/captcha-canvas'

const generateCaptcha = (): string => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  let captcha = ''
  for (let i = 0; i < 5; i++) {
    captcha += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return captcha
}

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [captchaInput, setCaptchaInput] = useState('')
  const [captchaCode, setCaptchaCode] = useState(generateCaptcha())
  const [captchaError, setCaptchaError] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const navigate = useNavigate()
  const location = useLocation()
  const { login } = useAuth()

  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/dashboard'

  const refreshCaptcha = () => {
    setCaptchaCode(generateCaptcha())
    setCaptchaInput('')
    setCaptchaError(false)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    setCaptchaError(false)

    if (captchaInput.toUpperCase() !== captchaCode) {
      setError('Gagal memuat captcha. Silakan refresh halaman.')
      setCaptchaError(true)
      setLoading(false)
      refreshCaptcha()
      return
    }

    try {
      await login(email, password)
      navigate(from, { replace: true })
    } catch (err: any) {
      setError(err.response?.data?.message || 'Email atau password salah')
      refreshCaptcha()
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    setCaptchaCode(generateCaptcha())
  }, [])

  return (
    <div 
      className="flex min-h-screen items-center justify-center relative overflow-hidden"
      style={{
        backgroundImage: 'url(/Latar.webp)',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat'
      }}
    >
      {/* Overlay */}
      <div className="absolute inset-0 bg-black/20 backdrop-blur-[2px]" />

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md relative z-10 px-4"
      >
        <div className="bg-white rounded-3xl shadow-2xl overflow-hidden">
          {/* Logo Section */}
          <div className="p-8 pb-6 text-center">
            <motion.div 
              className="flex justify-center mb-4"
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ type: 'spring', stiffness: 200, damping: 15 }}
            >
              <div className="w-24 h-24 bg-white rounded-3xl shadow-xl flex items-center justify-center p-2 border border-gray-100">
                <img 
                  src="/bsplogo.png" 
                  alt="PT. Bumi Siak Pusako" 
                  className="w-full h-full object-contain"
                />
              </div>
            </motion.div>
            <motion.h1 
              className="text-2xl font-bold text-[#1a4d3c]"
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2 }}
            >
              Sistem e-TLHT
            </motion.h1>
            <motion.p 
              className="text-sm text-gray-600 mt-1"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.3 }}
            >
              Silakan login untuk melanjutkan
            </motion.p>
          </div>

          {/* Form section */}
          <div className="px-8 pb-8">
            <AnimatePresence>
              {error && (
                <motion.div
                  initial={{ opacity: 0, y: -10, scale: 0.95 }}
                  animate={{ opacity: 1, y: 0, scale: 1 }}
                  exit={{ opacity: 0, y: -10, scale: 0.95 }}
                  className="p-4 text-sm text-red-700 bg-red-50 border border-red-300 rounded-xl mb-4 flex items-start gap-3 shadow-sm"
                >
                  <AlertTriangle size={18} className="mt-0.5 flex-shrink-0" />
                  <span className="font-medium">{error}</span>
                </motion.div>
              )}
            </AnimatePresence>

            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <Label htmlFor="email" className="text-gray-700 font-medium text-sm">
                  Email
                </Label>
                <div className="relative mt-1">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <User size={18} className="text-[#1a4d3c]" />
                  </div>
                  <Input
                    id="email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    autoComplete="email"
                    placeholder="email@example.com"
                    className="pl-10 bg-gray-50 border-gray-300 focus:border-[#1a4d3c] focus:ring-[#1a4d3c]"
                    required
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="password" className="text-gray-700 font-medium text-sm">
                  Password
                </Label>
                <div className="relative mt-1">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Lock size={18} className="text-[#1a4d3c]" />
                  </div>
                  <Input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    autoComplete="current-password"
                    placeholder="••••••••"
                    className="pl-10 pr-10 bg-gray-50 border-gray-300 focus:border-[#1a4d3c] focus:ring-[#1a4d3c]"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-[#1a4d3c] transition-colors"
                  >
                    {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
              </div>

              {/* Captcha */}
              <div>
                <Label htmlFor="captcha" className="text-gray-700 font-medium text-sm">
                  Kode Keamanan (Captcha)
                </Label>
                <div className="flex gap-2 mt-1">
                  <div className="relative flex-1">
                    <Input
                      id="captcha"
                      type="text"
                      value={captchaInput}
                      onChange={(e) => {
                        setCaptchaInput(e.target.value.toUpperCase())
                        setCaptchaError(false)
                      }}
                      placeholder="Memuat kode..."
                      className={`bg-gray-50 border-gray-300 focus:border-[#1a4d3c] focus:ring-[#1a4d3c] uppercase ${
                        captchaError ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''
                      }`}
                      maxLength={5}
                      required
                    />
                  </div>
                  <motion.button
                    type="button"
                    onClick={refreshCaptcha}
                    whileHover={{ scale: 1.05, rotate: 180 }}
                    whileTap={{ scale: 0.95 }}
                    transition={{ type: 'spring', stiffness: 300 }}
                    className="px-4 py-2 bg-gradient-to-br from-[#1a4d3c] to-[#164436] hover:from-[#164436] hover:to-[#1a4d3c] text-white border-none rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center"
                    title="Refresh Captcha"
                  >
                    <RefreshCw size={18} />
                  </motion.button>
                </div>
                
                {/* Captcha Hint
                <motion.div 
                  className="mt-3 flex items-center gap-2 p-3 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl shadow-sm"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: 0.4 }}
                >
                  <CheckCircle size={16} className="text-emerald-600 flex-shrink-0" />
                  <span className="text-emerald-700 text-xs font-medium">KETIK 5 KODE DI ATAS</span>
                </motion.div> */}
                
                {/* Captcha Canvas Display */}
                <motion.div 
                  className="mt-3 flex items-center justify-center p-1 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl shadow-inner"
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ delay: 0.3 }}
                  key={captchaCode}
                >
                  <CaptchaCanvas text={captchaCode} width={280} height={100} />
                </motion.div>
              </div>

              <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
                <Button 
                  type="submit" 
                  className="w-full mt-6 bg-gradient-to-r from-[#1a4d3c] to-[#164436] hover:from-[#164436] hover:to-[#1a4d3c] text-white font-semibold py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2" 
                  disabled={loading}
                >
                  <Lock size={18} />
                  {loading ? 'Memproses...' : 'Login'}
                </Button>
              </motion.div>
            </form>

            <motion.p 
              className="text-xs text-center text-gray-500 mt-6"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.5 }}
            >
              © 2026 Sistem e-TLHT - PT. Bumi Siak Pusako
            </motion.p>
          </div>
        </div>
      </motion.div>
    </div>
  )
}
