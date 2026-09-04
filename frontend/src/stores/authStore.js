import { defineStore } from 'pinia'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
  },

  actions: {
    async login(credentials) {
      this.loading = true
      this.error = null

      try {
        const { data } = await authService.login(credentials)

        this.setSession(data.user, data.token)

        return data
      } catch (err) {
        this.error =
          err.response?.data?.message ||
          'Unable to log in.'

        throw err
      } finally {
        this.loading = false
      }
    },

    async register(payload) {
      this.loading = true
      this.error = null

      try {
        const { data } = await authService.register(payload)

        this.setSession(data.user, data.token)

        return data
      } catch (err) {
        this.error =
          err.response?.data?.message ||
          'Unable to create your account.'

        throw err
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await authService.logout()
      } finally {
        this.clearSession()
      }
    },

    async fetchMe() {
      const { data } = await authService.me()

      this.user = data.user

      localStorage.setItem(
        'user',
        JSON.stringify(data.user),
      )

      return data.user
    },

    setSession(user, token) {
      this.user = user
      this.token = token

      localStorage.setItem('token', token)
      localStorage.setItem(
        'user',
        JSON.stringify(user),
      )
    },

    clearSession() {
      this.user = null
      this.token = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})