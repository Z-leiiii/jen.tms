<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
})
const emit = defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
      <div class="absolute inset-0 bg-ink/30" @click="emit('close')" />

      <!--
        Mobile: bottom sheet, full width, rounded top corners only, capped
        height with internal scroll so a tall form never pushes buttons
        off-screen below the fold.
        sm+: centered card, rounded all around, fixed max width.
      -->
      <div
        class="relative neo-raised w-full sm:max-w-md rounded-b-none sm:rounded-b-2xl
          max-h-[90vh] overflow-y-auto p-6"
      >
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold">{{ title }}</h2>
          <button
            class="min-h-touch min-w-touch flex items-center justify-center text-ink-soft"
            aria-label="Close"
            @click="emit('close')"
          >
            ✕
          </button>
        </div>

        <slot />
      </div>
    </div>
  </Teleport>
</template>