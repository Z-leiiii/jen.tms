<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useProjectStore } from '@/stores/projectStore'
import { useTaskStore } from '@/stores/taskStore'
import NeoButton from '@/components/ui/NeoButton.vue'
import KanbanBoard from '@/components/kanban/KanbanBoard.vue'
import TaskFormModal from '@/components/kanban/TaskFormModal.vue'
import ProjectMembersModal from '@/components/projects/ProjectMembersModal.vue'

const route = useRoute()
const projectStore = useProjectStore()
const taskStore = useTaskStore()

const showTaskModal = ref(false)
const showMembersModal = ref(false)
const editingTask = ref(null)

async function load(id) {
  await Promise.all([projectStore.fetchProject(id), taskStore.fetchTasks(id)])
}

onMounted(() => load(route.params.id))
watch(() => route.params.id, (id) => id && load(id))

function openNewTask() {
  editingTask.value = null
  showTaskModal.value = true
}

function openEditTask(task) {
  editingTask.value = task
  showTaskModal.value = true
}
</script>

<template>
  <div class="max-w-6xl mx-auto flex flex-col gap-6">
    <div v-if="projectStore.currentProject" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: projectStore.currentProject.color }" />
          <h1 class="text-xl sm:text-2xl font-bold truncate">{{ projectStore.currentProject.name }}</h1>
        </div>
        <p class="text-sm text-ink-soft">{{ projectStore.currentProject.description || 'No description yet.' }}</p>
      </div>
      <div class="flex gap-2 flex-shrink-0 self-start sm:self-auto">
        <NeoButton @click="showMembersModal = true">
          👥 {{ projectStore.currentProject.members_count ?? projectStore.currentProject.members?.length ?? 0 }}
        </NeoButton>
        <NeoButton variant="primary" @click="openNewTask">+ New task</NeoButton>
      </div>
    </div>

    <p v-if="taskStore.error" class="text-sm text-coral">{{ taskStore.error }}</p>
    <p v-else-if="taskStore.loading" class="text-sm text-ink-soft">Loading tasks…</p>

    <KanbanBoard v-else @task-click="openEditTask" />

    <TaskFormModal
      :open="showTaskModal"
      :project-id="route.params.id"
      :members="projectStore.currentProject?.members || []"
      :task="editingTask"
      @close="showTaskModal = false"
    />

    <ProjectMembersModal
      :open="showMembersModal"
      :project="projectStore.currentProject"
      @close="showMembersModal = false"
    />
  </div>
</template>