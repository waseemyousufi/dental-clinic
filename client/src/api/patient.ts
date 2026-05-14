import api from './api'
import type { PatientAllergyData } from './interfaces/Patient'
import type PatientData from './interfaces/Patient'
import fileManager from './utils/fileManager'
import { resolveBranchId } from './utils/branchParams'

export default new (class Patient {
  constructor() { }

  getBranchPatients(abbriviate: boolean = false, branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/patient', {
      params: {
        ...(abbriviate ? { abr: true } : {}),
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  getPatient(id: number) {
    return api.get(`/patient/${id}`)
  }

  postPatient(data: PatientData) {
    console.log('Posting patient with data:', data)
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

  setDebit(id: number,digit: number) {
    console.log(digit)
    return api.post(`set-patient-debit/${id}`, {
      debit: digit
    })
  }
})()
