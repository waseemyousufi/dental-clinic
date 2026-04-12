import api from './api'
import type ExpenseData from './interfaces/Expense'

export default new (class Expense {
  constructor() {}

  getBranchExpenses() {
    return api.get('/expense')
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
