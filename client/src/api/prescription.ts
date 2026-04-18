import api from './api'
import type PrescriptionData from './interfaces/Prescription'
import { resolveBranchId } from './utils/branchParams'

export default new (class Prescription {
  constructor() {}

  getBranchPrescriptions(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/prescription', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postPrescription(data: PrescriptionData) {
    return api.post('/prescription', data)
  }

  updatePrescription(id: Number, data: PrescriptionData) {
    return api.put(`/prescription/${id}`, data)
  }

  deletePrescriptionn(id: Number) {
    return api.delete(`/prescription/${id}`)
  }
})()
