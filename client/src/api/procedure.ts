import api from './api'

import type ProcedureData from './interfaces/Procedure'

export default new class {
  constructor () {}

  getProcedures() {
    return api.get('/procedure') as ProcedureData
  }

  postProcedure(data: ProcedureData) {
    return api.post('/procedure', data)
  }

  putProcedure(id: number,data: ProcedureData) {
    return api.put(`/procedure/${id}`, data)
  }

  deleteProcedure(id: number) {
    return api.delete(`/procedure/${id}`)
  }
}
