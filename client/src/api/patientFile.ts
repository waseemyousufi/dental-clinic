import api from './api'
import type PatientFileData from './interfaces/PatientFile'

export default new (class PatientFile {
  constructor() {}

  getBranchPatientFiles() {
    return api.get('/patient-files')
  }

  postPatientFile(data: PatientFileData) {
    return api.post('/patient-files', data)
  }

  updatePatientFile(id: Number, data: PatientFileData) {
    return api.put(`/patient-files/${id}`, data)
  }

  deletePatientFile(id: number) {
    return api.delete(`/patient-files/${id}`)
  }
})()
