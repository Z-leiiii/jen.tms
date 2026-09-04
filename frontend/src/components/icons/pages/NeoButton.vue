<script setup>
defineProps({
  variant: { type: String, default: 'default' }, // 'default' | 'primary'
  type: { type: String, default: 'button' },
  block: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})
defineEmits(['click'])
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="neo-btn"
    :class="[variant === 'primary' ? 'neo-btn-primary' : '', block ? 'w-full' : '', disabled ? 'opacity-50 pointer-events-none' : '']"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>