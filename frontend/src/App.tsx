import { Suspense, lazy } from 'react'
import { Routes, Route } from 'react-router-dom'
import { AuthProvider } from '@/contexts/AuthContext'
import { ProtectedRoute, PermissionRoute } from '@/components/shared'
import { AppLayout } from '@/layouts/AppLayout'

const HomePage = lazy(() => import('@/pages/HomePage'))
const DevComponentsPage = lazy(() => import('@/pages/DevComponentsPage'))
const LoginPage = lazy(() => import('@/pages/LoginPage'))
const Forbidden = lazy(() => import('@/pages/Forbidden'))
const NotFound = lazy(() => import('@/pages/NotFound'))
const DepartmentsPage = lazy(() => import('@/pages/master/DepartmentsPage'))
const EmployeesPage = lazy(() => import('@/pages/master/EmployeesPage'))
const UsersPage = lazy(() => import('@/pages/master/UsersPage'))
const PermissionsPage = lazy(() => import('@/pages/master/PermissionsPage'))
const AuditTrailPage = lazy(() => import('@/pages/AuditTrailPage'))
const InboxPage = lazy(() => import('@/pages/InboxPage'))
const ReportsPage = lazy(() => import('@/pages/ReportsPage'))
const FindingsPage = lazy(() => import('@/pages/findings/FindingsPage'))
const FindingFormPage = lazy(() => import('@/pages/findings/FindingFormPage'))
const FindingDetailPage = lazy(() => import('@/pages/findings/FindingDetailPage'))
const ActionPlansPage = lazy(() => import('@/pages/action-plans/ActionPlansPage'))
const ActionPlanFormPage = lazy(() => import('@/pages/action-plans/ActionPlanFormPage'))
const ActionPlanDetailPage = lazy(() => import('@/pages/action-plans/ActionPlanDetailPage'))
const FollowUpsPage = lazy(() => import('@/pages/follow-ups/FollowUpsPage'))
const FollowUpBatchFormPage = lazy(() => import('@/pages/follow-ups/FollowUpBatchFormPage'))
const PersetujuanPage = lazy(() => import('@/pages/follow-ups/PersetujuanPage'))
const IaMonitoringPage = lazy(() => import('@/pages/follow-ups/IaMonitoringPage'))
const SpiReviewQueuePage = lazy(() => import('@/pages/spi/SpiReviewQueuePage'))
const SpiReviewPage = lazy(() => import('@/pages/spi/SpiReviewPage'))
const ExternalStatusPage = lazy(() => import('@/pages/external/ExternalStatusPage'))
const ExternalStatusDetailPage = lazy(() => import('@/pages/external/ExternalStatusDetailPage'))

const Loading = () => <div className="flex h-screen items-center justify-center text-sm text-gray-500">Memuat...</div>

function App() {
  return (
    <AuthProvider>
      <Suspense fallback={<Loading />}>
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
              path="review-spi"
              element={
                <PermissionRoute menu="spi_review" action="view">
                  <SpiReviewQueuePage />
                </PermissionRoute>
              }
            />
            <Route
              path="review-spi/:actionPlanId"
              element={
                <PermissionRoute menu="spi_review" action="update">
                  <SpiReviewPage />
                </PermissionRoute>
              }
            />
            <Route
              path="status-eksternal"
              element={
                <PermissionRoute menu="external_status" action="view">
                  <ExternalStatusPage />
                </PermissionRoute>
              }
            />
            <Route
              path="status-eksternal/:findingId"
              element={
                <PermissionRoute menu="external_status" action="create">
                  <ExternalStatusDetailPage />
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
              path="inbox"
              element={
                <PermissionRoute menu="inbox" action="view">
                  <InboxPage />
                </PermissionRoute>
              }
            />
            <Route
              path="laporan"
              element={
                <PermissionRoute menu="reports" action="view">
                  <ReportsPage />
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
      </Suspense>
    </AuthProvider>
  )
}

export default App