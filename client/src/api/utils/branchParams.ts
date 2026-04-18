export function resolveBranchId(branchId?: number): number | undefined {
  if (typeof branchId === 'number' && Number.isFinite(branchId)) return branchId

  const raw = localStorage.getItem('selectedBranchId')
  if (!raw) return undefined
  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : undefined
}

