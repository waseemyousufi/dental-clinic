import api from './api'
import type OrderData from './interfaces/Order'

export default new (class Order {
  constructor() {}

  getOrders() { return api.get('/chores/orders') }
  getOrder(id: number) { return api.get(`/chores/orders/${id}`) }
  postOrder(data: OrderData) { return api.post('/chores/orders', data) }
  updateOrder(id: number, data: OrderData) { return api.put(`/chores/orders/${id}`, data) }
  deleteOrder(id: number) { return api.delete(`/chores/orders/${id}`) }
})()
