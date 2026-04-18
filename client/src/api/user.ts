import api from './api'
import type UserData from './interfaces/User'

export default new (class User {
  constructor() {}

  getMe() {
    return api.get('chores/me')
  }

  login(data: UserData) {
    return api.post('/login', data)
  }

  logout() {
    return api.post('chores/logout')
  }

  resetPassword(data: UserData) {
    return api.post('chores/reset-password', data)
  }

  sendTokenViaEmail(data: { token: string; email: string }) {
    return api.post('chores/send-token-via-email', data)
  }
})()
