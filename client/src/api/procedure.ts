import api from './api'

import type ProcedureData from './interfaces/Procedure'

export default new class {
  constructor () {}

  getProcedures(includeInactive = false) {
    const params = includeInactive ? { include_inactive: 'true' } : {}
    return api.get('/procedure', { params })
  }

  postProcedure(data: ProcedureData) {
    console.log(data)
    return api.post('/procedure', data)
  }

  putProcedure(id: number,data: ProcedureData) {
    console.log(data)
    return api.put(`/procedure/${id}`, data)
  }

  deleteProcedure(id: number) {
    return api.delete(`/procedure/${id}`)
  }
}
