import api from './api'
import type AccountData from './interfaces/Account'
import { resolveBranchId } from './utils/branchParams'

export default new (class Account {
  constructor() { }

  getBranchAccounts(showAllAccounts: boolean = false, branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/account', {
      params: {
        ...(showAllAccounts ? { all: true } : {}),
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postAccount(data: AccountData) {
    return api.post('/account', data)
  }

  updateAccount(id: number, data: AccountData) {
    return api.put(`/account/${id}`, data)
  }

  charge(id: number, amount: number, description?: string) {
    return api.post(`/account/${id}/charge`, {
      amount,
      ...(description ? { description } : {}),
    })
  }

  withdraw(id: number, amount: number, description?: string) {
    return api.post(`/account/${id}/withdraw`, {
      amount,
      ...(description ? { description } : {}),
    })
  }

  deleteAccount(id: number) {
    return api.delete(`/account/${id}`)
  }
})()
