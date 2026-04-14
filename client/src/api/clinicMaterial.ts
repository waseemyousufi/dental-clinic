import api from './api'
import type ClinicMaterialData from './interfaces/ClinicMaterial'

export default new (class ClinicMaterial {
  constructor() {}

  getBranchClinicMaterial() { return api.get('/material') }
  getClinicMaterial(id: number) { return api.get(`/material/${id}`) }
  postClinicMaterial(data: ClinicMaterialData) { return api.post('/material', data) }
  updateClinicMaterial(id: number, data: ClinicMaterialData) { return api.put(`/material/${id}`, data) }
  deleteClinicMaterial(id: number) { return api.delete(`/material/${id}`) }
})()
