import api from './api'
import type UserData from './interfaces/User'

export default new (class User {
  constructor() {}

  getMe() {
    return api.get('/me')
  }

  login(data: UserData) {
    return api.post('/login', data)
  }

  resetPassword(data: UserData) {
    return api.post('/reset-password', data)
  }

  sendTokenViaEmail(data: { token: String; email: String }) {
    return api.post('send-token-via-email', data)
  }
})()
