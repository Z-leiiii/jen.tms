<script setup>
import draggable from 'vuedraggable'
import { useTaskStore } from '@/stores/taskStore'
import TaskCard from '@/components/kanban/TaskCard.vue'

defineEmits(['task-click'])

const taskStore = useTaskStore()

const columns = [
  { key: 'todo', label: 'To do' },
  { key: 'in_progress', label: 'In progress' },
  { key: 'review', label: 'Review' },
  { key: 'completed', label: 'Completed' },
]

// vuedraggable mutates the bound array directly on drop; we read the
// resulting status off the column's own key and persist it via the store's
// optimistic moveTask action (rolls back if the API call fails).
function handleChange(status, event) {
  if (event.added) {
    taskStore.moveTask(event.added.element.id, status)
  }
}
</script>

<template>
  <!--
    Columns scroll horizontally as a group on mobile (snap scrolling) rather
    than squeezing 4 columns into a 375px screen — that would be the actual
    distortion. Each column keeps a readable fixed width at every size.
  -->
  <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory -mx-4 px-4 sm:mx-0 sm:px-0">
    <div
      v-for="col in columns"
      :key="col.key"
      class="neo-inset flex-shrink-0 w-[80vw] max-w-[300px] sm:w-[260px] p-4 snap-start"
    >
      <div class="flex items-center justify-between mb-4 px-1">
        <h3 class="text-xs font-bold uppercase tracking-wide text-ink-soft">{{ col.label }}</h3>
        <span class="text-xs font-semibold text-ink-soft">{{ taskStore.byStatus[col.key].length }}</span>
      </div>

      <draggable
        :list="taskStore.byStatus[col.key]"
        item-key="id"
        group="tasks"
        class="flex flex-col gap-3 min-h-[60px]"
        ghost-class="opacity-40"
        @change="(e) => handleChange(col.key, e)"
      >
        <template #item="{ element }">
          <TaskCard :task="element" @click="$emit('task-click', element)" />
        </template>
      </draggable>
    </div>
  </div>
</template>