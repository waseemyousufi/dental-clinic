import api from './api'
import type ClinicMaterialData from './interfaces/ClinicMaterial'

export default new (class ClinicMaterial {
  constructor() {}

  getBranchClinicMaterial() {
    return api.get('/material')
  }

  postClinicMaterial(data: ClinicMaterialData) {
    return api.post('/material', data)
  }

  updateClinicMaterial(id: Number, data: ClinicMaterialData) {
    return api.put(`/material/${id}`, data)
  }

  deleteClinicMaterial(id: Number) {
    return api.delete(`/material/${id}`)
  }
})()
