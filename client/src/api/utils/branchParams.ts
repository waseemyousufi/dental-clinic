// BUG Redundent function, should be removed and all calls to it should be replaced with the logic inside it

export function resolveBranchId(branchId?: number): number | undefined {
  if (typeof branchId === 'number' && Number.isFinite(branchId)) return branchId

  const raw = null
  if (!raw) return undefined
  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : undefined
}

