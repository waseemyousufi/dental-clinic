import api from './api'
import type AppointmentData from './interfaces/Appointment'
import { resolveBranchId } from './utils/branchParams'

export default new (class Appointment {
  constructor() {}

  getBranchAppointments(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/appointment', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postAppointment(data: AppointmentData) {
    return api.post('/appointment', data)
  }

  updateAppointment(id: Number, data: AppointmentData) {
    return api.put(`/appointment/${id}`, data)
  }

  deleteAppointment(id: Number) {
    return api.delete(`/appointment/${id}`)
  }
})()
