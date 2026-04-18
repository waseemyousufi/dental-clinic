import api from './api'
import type SupplierData from './interfaces/Supplier'

export default new (class Supplier {
  constructor() {}

  getBranchSuppliers() { return api.get('/chores/suppliers') }
  postSupplier(data: SupplierData) { return api.post('/chores/suppliers', data) }
  updateSupplier(id: number, data: SupplierData) { return api.put(`/chores/suppliers/${id}`, data) }
  deleteSupplier(id: number) { return api.delete(`/chores/suppliers/${id}`) }
})()
