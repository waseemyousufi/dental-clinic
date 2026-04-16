import api from './api'
import type BranchData from './interfaces/Branch'

export default new (class Branch {
  constructor() {}

  getBranches() { return api.get('/branches') }
  postBranch(data: BranchData) { return api.post('/branches', data) }
  putBranch(id: number, data: BranchData) { return api.put(`/branches/${id}`, data) }
  deleteBranch(id: number) { return api.delete(`/branches/${id}`) }
})()
