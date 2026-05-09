import api from './api'

export default new class Dashboard {
  constructor() {}

  getBranchDashboard(branchId?: number, days?: number) {
    return api.get('/dashboard', {
      params: {
        ...(branchId != null ? { branchId } : {}),
        ...(days != null ? { days } : {}),
      },
    })
  }
}
