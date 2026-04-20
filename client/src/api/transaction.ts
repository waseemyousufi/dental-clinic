import api from './api'
import type TransactionData from './interfaces/Transaction'
import { resolveBranchId } from './utils/branchParams'

export default new (class Transaction {
  constructor() {}

  getBranchTransactions(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/transaction', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postTransaction(data: TransactionData) {
    return api.post('/transaction', data)
  }

  updateTransaction(id: number, data: TransactionData) {
    return api.put(`/transaction/${id}`, data)
  }

  deleteTransaction(id: number) {
    return api.delete(`/transaction/${id}`)
  }
})()
