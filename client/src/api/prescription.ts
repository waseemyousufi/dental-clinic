import api from './api'
import type PrescriptionData from './interfaces/Prescription'
import { resolveBranchId } from './utils/branchParams'

export default new (class Prescription {
  constructor() {}

  getBranchPrescriptions(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    const params = resolvedBranchId != null ? { branchId: resolvedBranchId } : {}
    return api.get('/prescription', { params })
  }

  postPrescription(data: PrescriptionData) {
    return api.post('/prescription', data)
  }

  updatePrescription(id: number, data: PrescriptionData) {
    return api.put(`/prescription/${id}`, data)
  }

  deletePrescriptionn(id: number) {
    return api.delete(`/prescription/${id}`)
  }
})()
