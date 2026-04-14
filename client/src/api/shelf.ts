import api from './api'
import type ShelfData from './interfaces/Shelf'

export default new (class Shelf {
  constructor() {}

  getShelves() { return api.get('/chores/shelves') }
  getShelf(id: number) { return api.get(`/chores/shelves/${id}`) }
  postShelf(data: ShelfData) { return api.post('/chores/shelves', data) }
  updateShelf(id: number, data: ShelfData) { return api.put(`/chores/shelves/${id}`, data) }
  deleteShelf(id: number) { return api.delete(`/chores/shelves/${id}`) }
})()
