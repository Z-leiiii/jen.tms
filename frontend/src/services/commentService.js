import api from '@/services/api'

export const commentService = {
  list: (taskId) => api.get(`/tasks/${taskId}/comments`),
  create: (taskId, payload) => api.post(`/tasks/${taskId}/comments`, payload),
  update: (id, payload) => api.put(`/comments/${id}`, payload),
  delete: (id) => api.delete(`/comments/${id}`),
}
