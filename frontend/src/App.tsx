import { Routes, Route } from 'react-router-dom'
import { AuthProvider } from '@/contexts/AuthContext'
import { ProtectedRoute, PermissionRoute } from '@/components/shared'
import { AppLayout } from '@/layouts/AppLayout'
import HomePage from '@/pages/HomePage'
import DevComponentsPage from '@/pages/DevComponentsPage'
import LoginPage from '@/pages/LoginPage'
import Forbidden from '@/pages/Forbidden'
import NotFound from '@/pages/NotFound'

function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/403" element={<Forbidden />} />
        <Route path="/404" element={<NotFound />} />

        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<AppLayout />}>
            <Route
              index
              element={
                <PermissionRoute menu="dashboard" action="view">
                  <HomePage />
                </PermissionRoute>
              }
            />
            <Route
              path="dev/components"
              element={
                <PermissionRoute menu="dashboard" action="view">
                  <DevComponentsPage />
                </PermissionRoute>
              }
            />
            <Route path="*" element={<NotFound />} />
          </Route>
        </Route>

        <Route path="*" element={<NotFound />} />
      </Routes>
    </AuthProvider>
  )
}

export default App
