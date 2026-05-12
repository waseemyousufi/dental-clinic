import api from './api'
import type ClinicAssetData from './interfaces/ClinicAsset'
import { resolveBranchId } from './utils/branchParams'

export default new (class ClinicAsset {
  constructor() {}

  getBranchClinicAssets(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/assets', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }
  getClinicAsset(id: number) { return api.get(`/assets/${id}`) }
  postClinicAsset(data: ClinicAssetData) {
    console.log(data);
    return api.post('/assets', data) }
  updateClinicAsset(id: number, data: ClinicAssetData) { return api.put(`/assets/${id}`, data) }
  deleteClinicAsset(id: number) { return api.delete(`/assets/${id}`) }
})()
