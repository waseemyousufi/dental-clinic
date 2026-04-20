import api from './api'
import type InventoryStockData from './interfaces/InventoryStock'

export default new (class InventoryStock {
  constructor() {}

  getInventoryStock() { return api.get('/inventory-stock') }
  getInventoryStockItem(id: number) { return api.get(`/inventory-stock/${id}`) }
  postInventoryStock(data: InventoryStockData) { return api.post('/inventory-stock', data) }
  updateInventoryStock(id: number, data: InventoryStockData) { return api.put(`/inventory-stock/${id}`, data) }
  deleteInventoryStock(id: number) { return api.delete(`/inventory-stock/${id}`) }
  getPendingStock() { return api.get('/inventory-stock/pending') }
  getPlacedStock() { return api.get('/inventory-stock/placed') }
})()
