import { defineStore } from 'pinia'
import { taskService } from '@/services/taskService'

const STATUSES = ['todo', 'in_progress', 'review', 'completed']

export const useTaskStore = defineStore('task', {
  state: () => ({
    tasks: [],
    loading: false,
    error: null,
    filters: { status: '', priority: '', assigned_to: '', search: '' },
  }),

  getters: {
    // Kanban board reads this directly — one entry per column, in status order.
    byStatus: (state) => {
      const groups = Object.fromEntries(STATUSES.map((s) => [s, []]))
      for (const task of state.tasks) {
        (groups[task.status] ??= []).push(task)
      }
      return groups
    },
  },

  actions: {
    async fetchTasks(projectId, filters = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await taskService.listForProject(projectId, filters)
        this.tasks = data.data
        return this.tasks
      } catch (err) {
        this.error = "Couldn't load tasks for this project."
        throw err
      } finally {
        this.loading = false
      }
    },

    async createTask(payload) {
      const { data } = await taskService.create(payload)
      this.tasks.unshift(data.data)
      return data.data
    },

    async updateTask(id, payload) {
      const { data } = await taskService.update(id, payload)
      this.replaceInList(data.data)
      return data.data
    },

    // Optimistic — the Kanban drag interaction needs the card to move
    // instantly; we roll back on failure rather than waiting on the network.
    async moveTask(id, status) {
      const task = this.tasks.find((t) => t.id === id)
      const previousStatus = task?.status
      if (task) task.status = status

      try {
        const { data } = await taskService.move(id, status)
        this.replaceInList(data.data)
        return data.data
      } catch (err) {
        if (task && previousStatus) task.status = previousStatus
        throw err
      }
    },

    async assignTask(id, userId) {
      const { data } = await taskService.assign(id, userId)
      this.replaceInList(data.data)
      return data.data
    },

    async completeTask(id) {
      const { data } = await taskService.complete(id)
      this.replaceInList(data.data)
      return data.data
    },

    async duplicateTask(id) {
      const { data } = await taskService.duplicate(id)
      this.tasks.unshift(data.data)
      return data.data
    },

    async deleteTask(id) {
      await taskService.delete(id)
      this.tasks = this.tasks.filter((t) => t.id !== id)
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    replaceInList(task) {
      const idx = this.tasks.findIndex((t) => t.id === task.id)
      if (idx !== -1) this.tasks[idx] = task
    },
  },
})