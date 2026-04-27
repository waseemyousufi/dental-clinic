import api from './api'
import type UserData from './interfaces/User'

export default new (class User {
  constructor() { }

  getMe() {
    return api.get('/me')
  }

  login(data: UserData) {
    return api.post('/login', data)
  }

  logout() {
    return api.post('/logout')
  }

  resetPassword(data: UserData) {
    return api.post('/reset-password', data)
  }

  updateOwnProfilePicture(file: File) {
    const formData = new FormData()
    formData.append('image', file)

    return api.post('/update-profile-pic', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  }

  sendTokenViaEmail(data: { token: string; email: string }) {
    return api.post('/send-token-via-email', data)
  }
})()
