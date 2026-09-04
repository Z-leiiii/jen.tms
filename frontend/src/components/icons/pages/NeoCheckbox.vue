<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

function toggle() {
  emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <!-- Padded wrapper keeps the tap target at 44px even though the visual
       box is smaller — matches the touch-size floor without blowing up
       the checkbox's footprint inside a dense task list. -->
  <button
    type="button"
    role="checkbox"
    :aria-checked="modelValue"
    :aria-label="label || 'Toggle task complete'"
    class="min-h-touch min-w-touch flex items-center justify-center flex-shrink-0"
    @click="toggle"
  >
    <span
      class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold transition-all duration-150"
      :class="modelValue ? 'shadow-neo-inset-sm bg-emerald-soft text-emerald' : 'shadow-neo-xs sm:shadow-neo-sm text-transparent'"
    >
      ✓
    </span>
  </button>
</template>