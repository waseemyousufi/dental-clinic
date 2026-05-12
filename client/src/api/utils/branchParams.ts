export function resolveBranchId(branchId?: number): number | undefined {
  if (typeof branchId === 'number' && Number.isFinite(branchId)) return branchId

  const queryBranchId = new URLSearchParams(window.location.search).get('branchId')
  if (queryBranchId) {
    const parsedQueryBranchId = Number(queryBranchId)
    if (Number.isFinite(parsedQueryBranchId)) return parsedQueryBranchId
  }

  const storedUser = JSON.parse(localStorage.getItem('user') || '{}')
  const storedBranchId =
    storedUser?.user?.employee?.branchId ??
    storedUser?.user?.employee?.branch_id ??
    storedUser?.user?.branchId ??
    storedUser?.user?.branch_id

  const parsedStoredBranchId = Number(storedBranchId)
  return Number.isFinite(parsedStoredBranchId) ? parsedStoredBranchId : undefined
}
