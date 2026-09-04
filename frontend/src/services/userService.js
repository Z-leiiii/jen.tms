import api from '@/services/api'

export const userService = {
  search: (search) => api.get('/users', { params: { search } }),
  updateProfile: (payload) => api.put('/profile', payload),
  updatePassword: (payload) => api.put('/profile/password', payload),
}
