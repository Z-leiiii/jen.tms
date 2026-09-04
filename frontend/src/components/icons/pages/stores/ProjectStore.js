import { defineStore } from 'pinia'
import { projectService } from '@/services/projectService'

export const useProjectStore = defineStore('project', {
  state: () => ({
    projects: [],
    currentProject: null,
    statistics: null,
    loading: false,
    error: null,
  }),

  getters: {
    getById: (state) => (id) => state.projects.find((p) => p.id === id),
  },

  actions: {
    async fetchProjects(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await projectService.list(params)
        this.projects = data.data
        return this.projects
      } catch (err) {
        this.error = "Couldn't load your projects."
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchProject(id) {
      this.loading = true
      this.error = null
      try {
        const { data } = await projectService.get(id)
        this.currentProject = data.data
        return this.currentProject
      } catch (err) {
        this.error = "Couldn't load this project."
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchStatistics(id) {
      const { data } = await projectService.statistics(id)
      this.statistics = data.data
      return this.statistics
    },

    async createProject(payload) {
      const { data } = await projectService.create(payload)
      this.projects.unshift(data.data)
      return data.data
    },

    async updateProject(id, payload) {
      const { data } = await projectService.update(id, payload)
      this.replaceInList(data.data)
      if (this.currentProject?.id === id) this.currentProject = data.data
      return data.data
    },

    async deleteProject(id) {
      await projectService.delete(id)
      this.projects = this.projects.filter((p) => p.id !== id)
      if (this.currentProject?.id === id) this.currentProject = null
    },

    async archiveProject(id) {
      const { data } = await projectService.archive(id)
      this.replaceInList(data.data)
      return data.data
    },

    async addMember(id, payload) {
      const { data } = await projectService.addMember(id, payload)
      if (this.currentProject?.id === id) this.currentProject = data.data
      return data.data
    },

    async removeMember(id, userId) {
      const { data } = await projectService.removeMember(id, userId)
      if (this.currentProject?.id === id) this.currentProject = data.data
      return data.data
    },

    replaceInList(project) {
      const idx = this.projects.findIndex((p) => p.id === project.id)
      if (idx !== -1) this.projects[idx] = project
    },
  },
})