import api from './api'
import type ClinicOwnerData from './interfaces/ClinicOwner'

export default new (class ClinicOwner {
  constructor() {}

  getClinicOwners() { return api.get('/clinic-owner') }
  postClinicOwner(data: ClinicOwnerData) { return api.post('/clinic-owner', data) }
  putClinicOwner(id: number, data: ClinicOwnerData) { return api.put(`/clinic-owner/${id}`, data) }
  deleteClinicOwner(id: number) { return api.delete(`/clinic-owner/${id}`) }
})()
