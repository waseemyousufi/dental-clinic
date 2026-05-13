import { defineStore } from 'pinia'
import userApi from '@/api/user'

type UserPosition =
  | 'admin'
  | 'doctor'
  | 'doctor assistant'
  | 'receptionist'
  | string

const stored = localStorage.getItem('user')

let parsedUser: any = null
let parsedStoredSettings: {}|null = null

try {
  parsedUser = stored ? JSON.parse(stored)?.user : null
  const storedSettings = localStorage.getItem('settings')
  parsedStoredSettings = storedSettings ? JSON.parse(storedSettings) : null
  console.log(parsedStoredSettings)
} catch {
  parsedUser = null
}

const useUserStore = defineStore('user', {
  state: () => ({
    user: parsedUser,
    position: parsedUser?.employee?.position ?? null as UserPosition | null,
    settings: parsedStoredSettings,
  }),

  getters: {
    isAdmin: (state) => state.position === 'admin',

    isDoctor: (state) => state.position === 'doctor',

    isDoctorAssistant: (state) =>
      state.position === 'doctor assistant',

    isReceptionist: (state) =>
      state.position === 'receptionist',
    id: (state) => state.user?.id,
  },

  actions: {
    async fetchUser() {
      try {
        const res = await userApi.getMe()

        const apiUser = res.data?.data?.user

        this.user = apiUser
        this.position = apiUser?.employee?.position ?? null

        localStorage.setItem(
          'user',
          JSON.stringify({
            user: apiUser,
          }),
        )
      } catch (error) {
        console.error('Failed to fetch user:', error)

        this.user = null
        this.position = null
      }
    },

    clearUser() {
      this.user = null
      this.position = null

      localStorage.removeItem('user')
    },
  },
})

export default useUserStore
