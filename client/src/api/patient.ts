import api from './api'
import type { PatientAllergyData } from './interfaces/Patient'
import type PatientData from './interfaces/Patient'
import fileManager from './utils/fileManager'

export default new (class Patient {
  constructor() { }

  getBranchPatients(abbriviate: boolean = false) {
    if (abbriviate) return api.get('/patient?abr=true')
    return api.get('/patient')
  }

  getPatient(id: number) {
    return api.get(`/patient/${id}`)
  }

  postPatient(data: PatientData) {
    return api.post('/patient', data)
  }

  updatePatient(id: number, data: PatientData) {
    return api.put(`/patient/${id}`, data)
  }

  updateProfilePicture(id: number, file: File) {
    return fileManager.putFile(`/patient/${id}/profile-picture`, file as unknown as Blob)
  }

  deletePatient(id: number) {
    return api.delete(`/patient/${id}`)
  }

  setAllergy(data: PatientAllergyData) {
    return api.post('/patient-allergy', data)
  }
})()
