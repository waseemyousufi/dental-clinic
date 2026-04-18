import api from './api'
import type TreatmentData from './interfaces/Treatment'
import { resolveBranchId } from './utils/branchParams'

export default new (class Treatment {
  constructor() {}

  getBranchTreatments(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/treatment', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postTreatment(data: TreatmentData) {
    return api.post('/treatment', data)
  }

  updateTreatment(id: Number, data: TreatmentData) {
    return api.put(`/treatment/${id}`, data)
  }

  deleteTreatment(id: Number) {
    return api.delete(`/treatment/${id}`)
  }
})()
