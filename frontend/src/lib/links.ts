/** Route helper terpusat: subjek notifikasi/inbox → halaman terkait. */
export function subjectRoute(subjectType: string, id: number): string {
  if (subjectType.includes('Finding')) return `/temuan/${id}`
  if (subjectType.includes('ActionPlan')) return `/action-plan/${id}`
  if (subjectType.includes('FollowUp')) return '/tindak-lanjut'
  return '/'
}