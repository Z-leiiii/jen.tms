<script setup>
import { computed } from 'vue'

const props = defineProps({
  priority: { type: String, default: 'medium' },
})

const STYLES = {
  urgent: { label: 'Urgent', classes: 'bg-coral-soft text-coral' },
  high: { label: 'High', classes: 'bg-amber-soft text-amber' },
  medium: { label: 'Medium', classes: 'bg-indigo-soft text-indigo' },
  low: { label: 'Low', classes: 'bg-surface text-ink-soft' },
  done: { label: 'Done', classes: 'bg-emerald-soft text-emerald' },
}

const style = computed(() => STYLES[props.priority] ?? STYLES.medium)
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold flex-shrink-0"
    :class="style.classes"
  >
    {{ style.label }}
  </span>
</template>
