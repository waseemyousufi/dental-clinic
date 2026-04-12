import api from './api'
import type TransactionData from './interfaces/Transaction'

export default new (class Transaction {
  constructor() {}

  getBranchTransactions() {
    return api.get('/chores/transaction')
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
