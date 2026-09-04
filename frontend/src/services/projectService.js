import api from '@/services/api'

export const projectService = {
  list: (params = {}) => api.get('/projects', { params }),
  get: (id) => api.get(`/projects/${id}`),
  create: (payload) => api.post('/projects', payload),
  update: (id, payload) => api.put(`/projects/${id}`, payload),
  delete: (id) => api.delete(`/projects/${id}`),
  archive: (id) => api.post(`/projects/${id}/archive`),
  statistics: (id) => api.get(`/projects/${id}/statistics`),
  addMember: (id, payload) => api.post(`/projects/${id}/members`, payload),
  removeMember: (id, userId) => api.delete(`/projects/${id}/members/${userId}`),
}
