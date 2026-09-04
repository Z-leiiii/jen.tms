<script setup>
import PriorityPill from '@/components/ui/PriorityPill.vue'

defineProps({
  task: { type: Object, required: true },
})
defineEmits(['click'])
</script>

<template>
  <div
    class="neo-raised-compact p-4 cursor-pointer select-none"
    @click="$emit('click', task)"
  >
    <p class="text-sm font-semibold mb-2 line-clamp-2">{{ task.title }}</p>

    <div class="flex items-center justify-between gap-2">
      <PriorityPill :priority="task.status === 'completed' ? 'done' : task.priority" />
      <span v-if="task.due_date" class="text-xs font-mono text-ink-soft flex-shrink-0">
        {{ new Date(task.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}
      </span>
    </div>

    <div v-if="task.assignee" class="flex items-center gap-2 mt-3">
      <div class="w-6 h-6 rounded-full neo-inset-sm flex items-center justify-center text-[10px] font-semibold text-indigo flex-shrink-0">
        {{ task.assignee.name?.charAt(0).toUpperCase() }}
      </div>
      <span class="text-xs text-ink-soft truncate">{{ task.assignee.name }}</span>
    </div>
  </div>
</template>