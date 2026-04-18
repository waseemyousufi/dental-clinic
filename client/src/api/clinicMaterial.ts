import api from './api'
import type ClinicMaterialData from './interfaces/ClinicMaterial'
import { resolveBranchId } from './utils/branchParams'

export default new (class ClinicMaterial {
  constructor() {}

  getBranchClinicMaterial(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/material', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }
  getClinicMaterial(id: number) { return api.get(`/material/${id}`) }
  postClinicMaterial(data: ClinicMaterialData) { return api.post('/material', data) }
  updateClinicMaterial(id: number, data: ClinicMaterialData) { return api.put(`/material/${id}`, data) }
  deleteClinicMaterial(id: number) { return api.delete(`/material/${id}`) }
})()
