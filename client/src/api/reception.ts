import api from './api'
import type ReceptionData from './interfaces/Reception'
import { resolveBranchId } from './utils/branchParams'

export default new (class Reception {
  constructor() {}

  getBranchReceptions(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/reception', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postReception(data: ReceptionData) {
    return api.post('/reception', data)
  }

  updateReception(id: Number, data: ReceptionData) {
    return api.put(`/reception/${id}`, data)
  }

  deleteReception(id: number) {
    return api.delete(`/reception/${id}`)
  }
})()
