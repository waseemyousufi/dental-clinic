import api from './api'
import type PatientFileData from './interfaces/PatientFile'
import { resolveBranchId } from './utils/branchParams'

export default new (class PatientFile {
  constructor() {}

  getBranchPatientFiles(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/patient-files', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
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
