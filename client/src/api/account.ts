import api from './api'
import type AccountData from './interfaces/Account'

export default new (class Account {
  constructor() { }

  getBranchAccounts(showAllAccounts: boolean = false) {
    if (showAllAccounts) return api.get('/account?all=true')
    return api.get('/account')
  }

  postAccount(data: AccountData) {
    return api.post('/account', data)
  }

  updateAccount(id: number, data: AccountData) {
    return api.put(`/account/${id}`, data)
  }

  deleteAccount(id: number) {
    return api.delete(`/account/${id}`)
  }
})()
