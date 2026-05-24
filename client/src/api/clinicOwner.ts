import api from './api'
import type ClinicOwnerData from './interfaces/ClinicOwner'

export default new (class ClinicOwner {
  constructor() { }

  async getClinicOwners() {
    const res = await api.get('/clinic-owner')
    console.log(res)
    return res
  }
  postClinicOwner(data: ClinicOwnerData) {
    console.log('Posting clinic owner data:', data) // Debug log
    return api.post('/clinic-owner', data) }
  putClinicOwner(id: number, data: ClinicOwnerData) { return api.put(`/clinic-owner/${id}`, data) }
  deleteClinicOwner(id: number) { return api.delete(`/clinic-owner/${id}`) }
})()
