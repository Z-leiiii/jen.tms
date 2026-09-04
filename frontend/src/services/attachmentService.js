import api from '@/services/api'

export const attachmentService = {
  list: (taskId) => api.get(`/tasks/${taskId}/attachments`),
  upload: (taskId, file) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post(`/tasks/${taskId}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  delete: (id) => api.delete(`/attachments/${id}`),
}
