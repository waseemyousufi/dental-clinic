import api from './api'

import type ProcedureData from './interfaces/Procedure'

export default new class {
  constructor() { }

  getProcedures({ includeInactive = false, longTermOnly = false, shortTermOnly = false } = {}) {
    const params: any = {}
    params.include_inactive = includeInactive ? 'true' : 'false'

    if (shortTermOnly)
      params.short_term_only = shortTermOnly
    if (longTermOnly)
      params.long_term_only = longTermOnly

    return api.get('/procedure', { params })
  }

  postProcedure(data: ProcedureData) {
    return api.post('/procedure', data)
  }

  putProcedure(id: number, data: ProcedureData) {
    return api.put(`/procedure/${id}`, data)
  }

  deleteProcedure(id: number) {
    return api.delete(`/procedure/${id}`)
  }
}
