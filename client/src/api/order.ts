import api from './api'
import type OrderData from './interfaces/Order'

export default new (class Order {
  constructor() {}

  getBranchOrders() { return api.get('/orders') }
  getOrder(id: number) { return api.get(`/orders/${id}`) }
  postOrder(data: OrderData) {console.log(data); return api.post('/orders', data) }
  updateOrder(id: number, data: OrderData) { return api.put(`/orders/${id}`, data) }
  deleteOrder(id: number) { return api.delete(`/orders/${id}`) }
})()
