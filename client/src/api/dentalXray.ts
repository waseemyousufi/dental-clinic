import api from './api'
import type DentalXrayData from './interfaces/DentalXray'
import fileManager from './utils/fileManager'

export default new (class DentalXray {
  constructor() {}

  getBranchDentalXrays() {
    return api.get('chores/dental-xray')
  }

  postDentalXray(data: DentalXrayData) {
    return api.post('chores/dental-xray', data)
  }

  async putXrayImage(image: Blob) {
    return await fileManager.putFile('dental-xray', image)
  }

  getXrayImage(id: string) {
    fileManager.getFile('dental-xray', id)
  }

  updateDentalXray(id: number, data: DentalXrayData) {
    return api.put(`chores/dental-xray/${id}`, data)
  }

  deleteDentalXray(id: number) {
    return api.delete(`chores/dental-xray/${id}`)
  }
})()
