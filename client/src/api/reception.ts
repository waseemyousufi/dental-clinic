import api from './api'
import type ReceptionData from './interfaces/Reception'

export default new (class Reception {
  constructor() {}

  getBranchReceptions() {
    return api.get('/reception')
  }

  postReception(data: ReceptionData) {
    return api.post('/reception', data)
  }

  updateReception(id: Number, data: ReceptionData) {
    return api.put(`/reception/${id}`, data)
  }

  deleteReception(id: number) {
    return api.delete(`/reception/${id}`)
  }
})()
