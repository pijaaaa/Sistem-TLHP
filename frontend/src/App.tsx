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
import AuditTrailPage from '@/pages/AuditTrailPage'
import FindingsPage from '@/pages/findings/FindingsPage'
import FindingFormPage from '@/pages/findings/FindingFormPage'
import FindingDetailPage from '@/pages/findings/FindingDetailPage'
import ActionPlansPage from '@/pages/action-plans/ActionPlansPage'
import ActionPlanFormPage from '@/pages/action-plans/ActionPlanFormPage'
import ActionPlanDetailPage from '@/pages/action-plans/ActionPlanDetailPage'
import FollowUpsPage from '@/pages/follow-ups/FollowUpsPage'
import FollowUpBatchFormPage from '@/pages/follow-ups/FollowUpBatchFormPage'
import PersetujuanPage from '@/pages/follow-ups/PersetujuanPage'
import IaMonitoringPage from '@/pages/follow-ups/IaMonitoringPage'

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
              path="temuan"
              element={
                <PermissionRoute menu="findings" action="view">
                  <FindingsPage />
                </PermissionRoute>
              }
            />
            <Route
              path="temuan/baru"
              element={
                <PermissionRoute menu="findings" action="create">
                  <FindingFormPage />
                </PermissionRoute>
              }
            />
            <Route
              path="temuan/:id/edit"
              element={
                <PermissionRoute menu="findings" action="update">
                  <FindingFormPage />
                </PermissionRoute>
              }
            />
            <Route
              path="temuan/:id"
              element={
                <PermissionRoute menu="findings" action="view">
                  <FindingDetailPage />
                </PermissionRoute>
              }
            />
            <Route
              path="action-plan"
              element={
                <PermissionRoute menu="action_plans" action="view">
                  <ActionPlansPage />
                </PermissionRoute>
              }
            />
            <Route
              path="action-plan/baru"
              element={
                <PermissionRoute menu="action_plans" action="create">
                  <ActionPlanFormPage />
                </PermissionRoute>
              }
            />
            <Route
              path="action-plan/:id"
              element={
                <PermissionRoute menu="action_plans" action="view">
                  <ActionPlanDetailPage />
                </PermissionRoute>
              }
            />
            <Route
              path="action-plan/:id/tindak-lanjut/susun"
              element={
                <PermissionRoute menu="follow_ups" action="create">
                  <FollowUpBatchFormPage />
                </PermissionRoute>
              }
            />
            <Route
              path="tindak-lanjut"
              element={
                <PermissionRoute menu="follow_ups" action="view">
                  <FollowUpsPage />
                </PermissionRoute>
              }
            />
            <Route
              path="persetujuan"
              element={
                <PermissionRoute menu="follow_up_reviews" action="view">
                  <PersetujuanPage />
                </PermissionRoute>
              }
            />
            <Route
              path="pemantauan"
              element={
                <PermissionRoute menu="ia_monitoring" action="view">
                  <IaMonitoringPage />
                </PermissionRoute>
              }
            />
            <Route
              path="audit-trail"
              element={
                <PermissionRoute menu="audit_trail" action="view">
                  <AuditTrailPage />
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