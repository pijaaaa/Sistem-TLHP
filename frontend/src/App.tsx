import { Routes, Route } from 'react-router-dom'
import { AuthProvider } from '@/contexts/AuthContext'
import { ProtectedRoute, PermissionRoute } from '@/components/shared'
import { AppLayout } from '@/layouts/AppLayout'
import HomePage from '@/pages/HomePage'
import DevComponentsPage from '@/pages/DevComponentsPage'
import LoginPage from '@/pages/LoginPage'
import Forbidden from '@/pages/Forbidden'
import NotFound from '@/pages/NotFound'
import DepartmentsPage from '@/pages/master/DepartmentsPage'
import EmployeesPage from '@/pages/master/EmployeesPage'
import UsersPage from '@/pages/master/UsersPage'
import PermissionsPage from '@/pages/master/PermissionsPage'
import FindingsReportsPage from '@/pages/findings/FindingsReportsPage'
import FindingsPage from '@/pages/findings/FindingsPage'
import FindingsDistributionPage from '@/pages/findings/FindingsDistributionPage'
import FindingDepartmentsPage from '@/pages/findings/FindingDepartmentsPage'

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
              path="master/departments"
              element={
                <PermissionRoute menu="master.departments" action="view">
                  <DepartmentsPage />
                </PermissionRoute>
              }
            />
            <Route
              path="master/employees"
              element={
                <PermissionRoute menu="master.employees" action="view">
                  <EmployeesPage />
                </PermissionRoute>
              }
            />
            <Route
              path="master/users"
              element={
                <PermissionRoute menu="master.employees" action="view">
                  <UsersPage />
                </PermissionRoute>
              }
            />
            <Route
              path="access/permissions"
              element={
                <PermissionRoute menu="access.permissions" action="view">
                  <PermissionsPage />
                </PermissionRoute>
              }
            />
            <Route
              path="findings/reports"
              element={
                <PermissionRoute menu="findings.reports" action="view">
                  <FindingsReportsPage />
                </PermissionRoute>
              }
            />
            <Route
              path="findings"
              element={
                <PermissionRoute menu="findings.list" action="view">
                  <FindingsPage />
                </PermissionRoute>
              }
            />
            <Route
              path="findings/distribution"
              element={
                <PermissionRoute menu="findings.distribution" action="view">
                  <FindingsDistributionPage />
                </PermissionRoute>
              }
            />
            <Route
              path="finding-departments"
              element={
                <PermissionRoute menu="findings.list" action="view">
                  <FindingDepartmentsPage />
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
