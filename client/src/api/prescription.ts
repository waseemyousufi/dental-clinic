import api from './api'
import type PrescriptionData from './interfaces/Prescription'

export default new (class Prescription {
  constructor() {}

  getBranchPrescriptions() {
    return api.get('/prescription')
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
