import api from './api'
import type TransactionData from './interfaces/Transaction'
import { resolveBranchId } from './utils/branchParams'

export default new (class Transaction {
  constructor() {}

  getBranchTransactions(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/chores/transaction', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postTransaction(data: TransactionData) {
    return api.post('/chores/transaction', data)
  }

  updateTransaction(id: Number, data: TransactionData) {
    return api.put(`/chores/transaction/${id}`, data)
  }

  deleteTransaction(id: Number) {
    return api.delete(`/chores/transaction/${id}`)
  }
})()
