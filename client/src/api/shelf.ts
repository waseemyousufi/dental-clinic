import api from './api'
import type ShelfData from './interfaces/Shelf'

export default new (class Shelf {
  constructor() {}

  getShelves() { return api.get('/shelves') }
  getShelf(id: number) { return api.get(`/shelves/${id}`) }
  postShelf(data: ShelfData) { return api.post('/shelves', data) }
  updateShelf(id: number, data: ShelfData) { return api.put(`/shelves/${id}`, data) }
  deleteShelf(id: number) { return api.delete(`/shelves/${id}`) }
})()
