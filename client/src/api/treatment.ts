import api from './api'
import type TreatmentData from './interfaces/Treatment'

export default new (class Treatment {
  constructor() {}

  getBranchTreatments() {
    return api.get('/treatment')
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
