import api from './api'
import type BranchData from './interfaces/Branch'

export default new (class Branch {
  constructor() {}

  getAllBranches() { return api.get('/all-branches') }
  getBranches() { return api.get('/branch') }
  postBranch(data: BranchData) {
    console.log('Posting branch data:', data) // Debug log
    return api.post('/branch', data) }
  putBranch(id: number, data: BranchData) { return api.put(`/branch/${id}`, data) }
  deleteBranch(id: number) { return api.delete(`/branch/${id}`) }
})()
