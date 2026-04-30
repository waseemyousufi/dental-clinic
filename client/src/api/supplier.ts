import api from './api'
import type SupplierData from './interfaces/Supplier'

export default new (class Supplier {
  constructor() {}

  getBranchSuppliers() { return api.get('/suppliers') }
  postSupplier(data: SupplierData) { return api.post('/suppliers', data) }
  updateSupplier(id: number, data: SupplierData) {console.log('Api Sent Data: ', data); return api.put(`/suppliers/${id}`, data) }
  deleteSupplier(id: number) { return api.delete(`/suppliers/${id}`) }
})()
