import api from './api'
import type ExpenseData from './interfaces/Expense'
import { resolveBranchId } from './utils/branchParams'

export default new (class Expense {
  constructor() {}

  getBranchExpenses(branchId?: number) {
    const resolvedBranchId = resolveBranchId(branchId)
    return api.get('/expense', {
      params: {
        ...(resolvedBranchId != null ? { branchId: resolvedBranchId } : {}),
      },
    })
  }

  postExpense(data: ExpenseData) {
    return api.post('/expense', data)
  }

  updateExpense(id: Number, data: ExpenseData) {
    return api.put(`/expense/${id}`, data)
  }

  deleteExpense(id: number) {
    return api.delete(`/expense/${id}`)
  }
})()
