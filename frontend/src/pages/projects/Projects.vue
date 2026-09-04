<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectStore } from '@/stores/projectStore'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoButton from '@/components/ui/NeoButton.vue'
import ProjectFormModal from '@/components/projects/ProjectFormModal.vue'

const router = useRouter()
const projectStore = useProjectStore()
const showCreateModal = ref(false)

onMounted(() => projectStore.fetchProjects())

function openProject(id) {
  router.push({ name: 'project-details', params: { id } })
}
</script>

<template>
  <div class="max-w-6xl mx-auto flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h1 class="text-xl sm:text-2xl font-bold">Your projects</h1>
      <NeoButton variant="primary" class="self-start sm:self-auto" @click="showCreateModal = true">
        + New project
      </NeoButton>
    </div>

    <p v-if="projectStore.loading" class="text-sm text-ink-soft">Loading…</p>
    <p v-else-if="projectStore.error" class="text-sm text-coral">{{ projectStore.error }}</p>

    <NeoCard v-else-if="projectStore.projects.length === 0">
      <p class="font-semibold mb-1">Start your first project</p>
      <p class="text-sm text-ink-soft mb-4">
        Group your assignments and group work into a project to track everything in one place.
      </p>
      <NeoButton variant="primary" @click="showCreateModal = true">Create project</NeoButton>
    </NeoCard>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <NeoCard
        v-for="project in projectStore.projects"
        :key="project.id"
        compact
        class="cursor-pointer"
        @click="openProject(project.id)"
      >
        <div class="flex items-start justify-between gap-2 mb-3">
          <h3 class="font-semibold truncate">{{ project.name }}</h3>
          <span class="w-3 h-3 rounded-full flex-shrink-0 mt-1" :style="{ backgroundColor: project.color }" />
        </div>
        <p class="text-sm text-ink-soft line-clamp-2 mb-4">{{ project.description || 'No description yet.' }}</p>
        <div class="flex items-center justify-between text-xs text-ink-soft">
          <span>{{ project.members_count ?? 0 }} members</span>
          <span>{{ project.tasks_count ?? 0 }} tasks</span>
        </div>
      </NeoCard>
    </div>

    <ProjectFormModal :open="showCreateModal" @close="showCreateModal = false" />
  </div>
</template>