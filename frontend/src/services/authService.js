import api from '@/services/api'

export const authService = {
  login(credentials) {
    return api.post('/login', credentials)
  },

  register(payload) {
    return api.post('/register', payload)
  },

  logout() {
    return api.post('/logout')
  },

  me() {
    return api.get('/me')
  },

  forgotPassword(payload) {
    return api.post('/forgot-password', payload)
  },

  resetPassword(payload) {
    return api.post('/reset-password', payload)
  },
}