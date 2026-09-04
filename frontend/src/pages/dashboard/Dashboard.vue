<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { projectService } from '@/services/projectService'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const auth = useAuthStore()
const projects = ref([])
const loading = ref(true)
const errorMsg = ref('')

const stats = computed(() => ({
  total: projects.value.length,
  active: projects.value.filter((p) => p.status === 'active').length,
  archived: projects.value.filter((p) => p.status === 'archived').length,
}))

async function loadProjects() {
  loading.value = true
  errorMsg.value = ''
  try {
    const { data } = await projectService.list()
    projects.value = data.data
  } catch {
    errorMsg.value = "Couldn't load your projects. Try refreshing."
  } finally {
    loading.value = false
  }
}

onMounted(loadProjects)
</script>

<template>
  <div class="flex flex-col gap-6 max-w-6xl mx-auto">
    <!-- Header row: stacks on mobile, sits side-by-side from sm up -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold">Hey, {{ auth.user?.name?.split(' ')[0] || 'there' }}</h1>
        <p class="text-sm text-ink-soft mt-1">Here's where things stand across your projects.</p>
      </div>
      <NeoButton variant="primary" class="self-start sm:self-auto">+ New project</NeoButton>
    </div>

    <!-- Stat cards: 1 col on mobile, 3 across from sm up -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <NeoCard compact>
        <p class="text-xs font-semibold text-ink-soft mb-2">Total projects</p>
        <p class="text-2xl font-bold font-display">{{ stats.total }}</p>
      </NeoCard>
      <NeoCard compact>
        <p class="text-xs font-semibold text-ink-soft mb-2">Active</p>
        <p class="text-2xl font-bold font-display text-emerald">{{ stats.active }}</p>
      </NeoCard>
      <NeoCard compact>
        <p class="text-xs font-semibold text-ink-soft mb-2">Archived</p>
        <p class="text-2xl font-bold font-display text-ink-soft">{{ stats.archived }}</p>
      </NeoCard>
    </div>

    <!-- Projects grid: 1 col mobile, 2 tablet, 3 desktop -->
    <div>
      <h2 class="text-base font-semibold mb-3">Your projects</h2>

      <p v-if="loading" class="text-sm text-ink-soft">Loading your projects…</p>
      <p v-else-if="errorMsg" class="text-sm text-coral">{{ errorMsg }}</p>

      <NeoCard v-else-if="projects.length === 0">
        <p class="font-semibold mb-1">Start your first project</p>
        <p class="text-sm text-ink-soft mb-4">Group your assignments and group work into a project to track everything in one place.</p>
        <NeoButton variant="primary">Create project</NeoButton>
      </NeoCard>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <NeoCard v-for="project in projects" :key="project.id" compact>
          <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="font-semibold truncate">{{ project.name }}</h3>
            <span
              class="w-3 h-3 rounded-full flex-shrink-0 mt-1"
              :style="{ backgroundColor: project.color }"
            />
          </div>
          <p class="text-sm text-ink-soft line-clamp-2 mb-4">{{ project.description || 'No description yet.' }}</p>
          <div class="flex items-center justify-between text-xs text-ink-soft">
            <span>{{ project.members_count ?? 0 }} members</span>
            <span>{{ project.tasks_count ?? 0 }} tasks</span>
          </div>
        </NeoCard>
      </div>
    </div>
  </div>
</template>