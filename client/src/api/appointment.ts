import api from './api'
import type AppointmentData from './interfaces/Appointment'

export default new (class Appointment {
  constructor() {}

  getBranchAppointments() {
    return api.get('/appointment')
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
