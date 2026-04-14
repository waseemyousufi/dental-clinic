import api from './api'
import type InventoryStockData from './interfaces/InventoryStock'

export default new (class InventoryStock {
  constructor() {}

  getInventoryStock() { return api.get('/chores/inventory-stock') }
  getInventoryStockItem(id: number) { return api.get(`/chores/inventory-stock/${id}`) }
  postInventoryStock(data: InventoryStockData) { return api.post('/chores/inventory-stock', data) }
  updateInventoryStock(id: number, data: InventoryStockData) { return api.put(`/chores/inventory-stock/${id}`, data) }
  deleteInventoryStock(id: number) { return api.delete(`/chores/inventory-stock/${id}`) }
  getPendingStock() { return api.get('/chores/inventory-stock/pending') }
  getPlacedStock() { return api.get('/chores/inventory-stock/placed') }
})()
