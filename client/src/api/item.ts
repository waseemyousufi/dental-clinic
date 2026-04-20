import api from './api'
import type ItemData from './interfaces/Item'

export default new (class Item {
  constructor() {}

  getItems(params?: { category?: string; consumable?: boolean; track_stock?: boolean; search?: string }) {
    return api.get('/items', { params })
  }
  getItem(id: number) { return api.get(`/items/${id}`) }
  postItem(data: ItemData) { return api.post('/items', data) }
  updateItem(id: number, data: ItemData) { return api.put(`/items/${id}`, data) }
  deleteItem(id: number) { return api.delete(`/items/${id}`) }
  getCategories() { return api.get('/items/categories') }
})()
