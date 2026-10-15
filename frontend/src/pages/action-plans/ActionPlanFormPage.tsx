import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { ActionPlanForm, type ActionPlanFormData } from '@/components/shared/action-plan-form'
import { PageHeader, Can } from '@/components/shared'
import { Select, Spinner } from '@/components/ui'
import { useToast } from '@/components/ui/toast'
import { useFinding, useFindings } from '@/hooks/useFindings'
import { useCreateActionPlan, useUploadActionPlanDocument } from '@/hooks/useActionPlans'
import { usePermission } from '@/hooks/usePermission'
import type { Finding } from '@/types/finding'
import { isAxiosError } from 'axios'
import { motion } from 'framer-motion'

export default function ActionPlanFormPage() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { showToast } = useToast()
  const temuanParam = searchParams.get('temuan')
  const findingId = temuanParam ? Number(temuanParam) : null
  const [finding, setFinding] = useState<Finding | null>(null)
  const [findingOptions, setFindingOptions] = useState<Finding[]>([])

  const { data: loadedFinding } = useFinding(findingId ?? 0)
  const permFindings = usePermission('findings')
  const { data: findingsPage } = useFindings({ per_page: 100 }, { enabled: permFindings.view })

  const create = useCreateActionPlan()
  const uploadDoc = useUploadActionPlanDocument()

  useEffect(() => {
    if (loadedFinding) setFinding(loadedFinding)
  }, [loadedFinding])

  useEffect(() => {
    setFindingOptions(findingsPage?.data ?? [])
  }, [findingsPage])

  const resultDepts = useMemo(() => finding?.auditee_departments ?? [], [finding])

  const submit = async (data: ActionPlanFormData) => {
    if (!finding) {
      showToast('Pilih temuan terlebih dahulu.', 'error')
      return
    }
    try {
      const lossIdr = data.loss_idr !== '' ? Number(data.loss_idr.replace(/\D/g, '')) : null
      const lossUsd = data.loss_usd !== '' ? Number(data.loss_usd.replace(',', '.')) : null
      const aps = await create.mutateAsync({
        finding_id: finding.id,
        department_ids: data.department_ids,
        title: data.title,
        condition: data.condition || null,
        criteria: data.criteria || null,
        cause: data.cause || null,
        impact: data.impact || null,
        risk: data.risk || null,
        deadline: data.deadline || null,
        deadline_per_department: Object.fromEntries(
          Object.entries(data.deadline_per_department).filter(([, v]) => Boolean(v)),
        ),
        loss_idr: lossIdr,
        loss_usd: lossUsd,
      })

      for (const ap of aps) {
        if (data.files.length > 0) {
          await uploadDoc.mutateAsync({
            id: ap.id,
            files: data.files.map((f) => ({ file: f.file, label: f.label || 'Dokumen' })),
          })
        }
      }

      showToast(`${aps.length} action plan berhasil dibuat.`, 'success')
      navigate(`/temuan/${finding.id}`, { state: { tab: 'action-plan' } })
    } catch (e) {
      showToast(isAxiosError(e) ? e.response?.data?.message ?? 'Gagal membuat action plan.' : 'Gagal membuat action plan.', 'error')
    }
  }

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      <PageHeader 
        title="Buat Action Plan" 
        subtitle="Satu formulir untuk satu action plan per departemen" 
      />

      <Can menu="action_plans" action="create">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
          className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 md:p-8"
        >
          {findingId === null && (
            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <label className="block text-sm font-medium text-gray-900 mb-2">
                Pilih Temuan
              </label>
              <Select
                value={finding?.id ?? ''}
                onChange={(e) => {
                  const f = findingOptions.find((x) => x.id === Number(e.target.value))
                  setFinding(f ?? null)
                }}
                className="max-w-2xl"
              >
                <option value="">-- Pilih Temuan --</option>
                {findingOptions.map((f) => (
                  <option key={f.id} value={f.id}>
                    {f.registration_number ?? `Draft #${f.id}`} — {f.title}
                  </option>
                ))}
              </Select>
            </div>
          )}

          {!finding && findingId !== null ? (
            <div className="flex items-center justify-center py-12">
              <div className="flex items-center gap-2 text-sm text-gray-500">
                <Spinner /> Memuat temuan...
              </div>
            </div>
          ) : finding ? (
            <ActionPlanForm
              finding={finding}
              departments={resultDepts}
              submitting={create.isPending}
              onSubmit={submit}
            />
          ) : (
            <div className="text-center py-12">
              <p className="text-sm text-gray-500">Pilih temuan untuk memulai membuat action plan.</p>
            </div>
          )}
        </motion.div>
      </Can>

      {finding && finding.status === 'TERDAFTAR' && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <p className="text-sm text-yellow-800">
            <strong>Catatan:</strong> Temuan belum diaktifkan; action plan dapat dibuat dan dikirim.
          </p>
        </div>
      )}
    </div>
  )
}