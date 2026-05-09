import api from './api'
import type AppointmentData from './interfaces/Appointment'
import { resolveBranchId } from './utils/branchParams'
import useUserStore from '@/stores/user'
const userStore = useUserStore()

export default new (class Appointment {
  constructor() {}

  getBranchAppointments(branchId?: number) {
    if(userStore.isDoctor)
      return api.get('/doc/appointment')
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/appointment', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postAppointment(data: AppointmentData) {
    console.log(data)
    return api.post('/appointment', data)
  }

  updateAppointment(id: number, data: AppointmentData) {
    return api.put(`/appointment/${id}`, data)
  }

  deleteAppointment(id: number) {
    return api.delete(`/appointment/${id}`)
  }
})()
