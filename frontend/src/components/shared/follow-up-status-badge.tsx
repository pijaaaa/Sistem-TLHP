import { StatusBadge } from '@/components/ui'
import { getFollowUpStatusVariant } from '@/types/finding'

const FollowUpStatusBadge = ({ status, label }: { status?: string | null; label?: string }) => (
  <StatusBadge status={label ?? status ?? '-'} variant={getFollowUpStatusVariant(status)} />
)

export { FollowUpStatusBadge }