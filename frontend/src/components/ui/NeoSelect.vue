<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  options: { type: Array, default: () => [] }, // [{ value, label }]
  placeholder: { type: String, default: 'Select…' },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <label class="block w-full">
    <span v-if="label" class="block text-xs font-semibold text-ink-soft mb-2">{{ label }}</span>
    <select
      :value="modelValue"
      class="neo-field appearance-none"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
    </select>
  </label>
</template>