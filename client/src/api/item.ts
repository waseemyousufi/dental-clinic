import api from './api'
import type ItemData from './interfaces/Item'

export default new (class Item {
  constructor() {}

  getItems(params?: { category?: string; consumable?: boolean; track_stock?: boolean; search?: string }) {
    return api.get('/chores/items', { params })
  }
  getItem(id: number) { return api.get(`/chores/items/${id}`) }
  postItem(data: ItemData) { return api.post('/chores/items', data) }
  updateItem(id: number, data: ItemData) { return api.put(`/chores/items/${id}`, data) }
  deleteItem(id: number) { return api.delete(`/chores/items/${id}`) }
  getCategories() { return api.get('/chores/items/categories') }
})()
