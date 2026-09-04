import api from '@/services/api'

export const taskService = {
  listForProject: (projectId, params = {}) => api.get(`/projects/${projectId}/tasks`, { params }),
  create: (payload) => api.post('/tasks', payload),
  get: (id) => api.get(`/tasks/${id}`),
  update: (id, payload) => api.put(`/tasks/${id}`, payload),
  delete: (id) => api.delete(`/tasks/${id}`),
  move: (id, status) => api.patch(`/tasks/${id}/move`, { status }),
  assign: (id, userId) => api.patch(`/tasks/${id}/assign`, { assigned_to: userId }),
  complete: (id) => api.post(`/tasks/${id}/complete`),
  duplicate: (id) => api.post(`/tasks/${id}/duplicate`),
}
