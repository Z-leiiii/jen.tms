<script setup>
defineProps({
  compact: { type: Boolean, default: false },
  padding: { type: String, default: 'p-5 sm:p-6' },
})
</script>

<template>
  <div :class="[compact ? 'neo-raised-compact' : 'neo-raised', padding]">
    <slot/>
  </div>
</template>