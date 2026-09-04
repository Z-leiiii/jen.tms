import api from '@/services/api'

export const authService = {
  login: (credentials) => api.post('/login', credentials),
  register: (payload) => api.post('/register', payload),
  logout: () => api.post('/logout'),
  me: () => api.get('/me'),
  forgotPassword: (payload) => api.post('/forgot-password', payload),
  resetPassword: (payload) => api.post('/reset-password', payload),
}
