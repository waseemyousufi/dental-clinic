import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    loading: true
  }),

  getters: {
    isLoggedIn: (state) => !!state.user?.token
  },

  actions: {
    async fetchUser() {
      if (!this.user?.token) {
        this.user = null
        this.loading = false
        return
      }

      try {
        const res = await axios.get('/api/me', {
          headers: {
            Authorization: `Bearer ${this.user.token}`
          }
        })

        // update user data (keep token!)
        this.user = {
          ...res.data.user,
          token: this.user.token
        }

        localStorage.setItem('user', JSON.stringify(this.user))
      } catch (e) {
        this.logout()
      }

      this.loading = false
    },

    async login(userData) {
      // userData already includes token
      this.user = userData
      localStorage.setItem('user', JSON.stringify(userData))

      await this.fetchUser()
    },

    logout() {
      this.user = null
      localStorage.removeItem('user')
    }
  }
})
