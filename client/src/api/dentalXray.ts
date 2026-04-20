import api from './api'
import type DentalXrayData from './interfaces/DentalXray'
import fileManager from './utils/fileManager'
import { resolveBranchId } from './utils/branchParams'

export default new (class DentalXray {
  constructor() {}

  getBranchDentalXrays(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/dental-xray', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postDentalXray(data: DentalXrayData) {
    return api.post('/dental-xray', data)
  }

  async putXrayImage(image: Blob) {
    return await fileManager.putFile('dental-xray', image)
  }

  getXrayImage(id: string) {
    fileManager.getFile('dental-xray', id)
  }

  updateDentalXray(id: number, data: DentalXrayData) {
    return api.put(`/dental-xray/${id}`, data)
  }

  deleteDentalXray(id: number) {
    return api.delete(`/dental-xray/${id}`)
  }
})()
