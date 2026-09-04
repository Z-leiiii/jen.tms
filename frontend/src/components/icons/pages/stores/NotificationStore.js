import { defineStore } from 'pinia'
import { notificationService } from '@/services/notificationService'

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
  }),

  actions: {
    async fetch(params = {}) {
      this.loading = true
      try {
        const { data } = await notificationService.list(params)
        this.notifications = data.data
        this.unreadCount = data.meta.unread_count
        return this.notifications
      } finally {
        this.loading = false
      }
    },

    async markRead(id) {
      const notif = this.notifications.find((n) => n.id === id)
      if (notif && !notif.is_read) {
        notif.is_read = true
        this.unreadCount = Math.max(0, this.unreadCount - 1)
      }
      await notificationService.markRead(id)
    },

    async markAllRead() {
      this.notifications.forEach((n) => (n.is_read = true))
      this.unreadCount = 0
      await notificationService.markAllRead()
    },

    async remove(id) {
      const notif = this.notifications.find((n) => n.id === id)
      this.notifications = this.notifications.filter((n) => n.id !== id)
      if (notif && !notif.is_read) this.unreadCount = Math.max(0, this.unreadCount - 1)
      await notificationService.delete(id)
    },
  },
})