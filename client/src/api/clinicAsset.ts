import api from './api'
import type ClinicAssetData from './interfaces/ClinicAsset'

export default new (class ClinicAsset {
  constructor() {}

  getBranchClinicAssets() {
    return api.get('/assets')
  }

  postClinicAsset(data: ClinicAssetData) {
    return api.post('/assets', data)
  }

  updateClinicAsset(id: number, data: ClinicAssetData) {
    return api.put(`/assets/${id}`, data)
  }

  deleteClinicAsset(id: number) {
    return api.delete(`/assets/${id}`)
  }
})()
