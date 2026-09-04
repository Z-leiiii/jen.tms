<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  autocomplete: { type: String, default: 'off' },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <label class="block w-full">
    <span v-if="label" class="block text-xs font-semibold text-ink-soft mb-2">{{ label }}</span>
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :autocomplete="autocomplete"
      class="neo-field"
      :class="error ? 'ring-2 ring-coral' : ''"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <span v-if="error" class="block text-xs text-coral mt-1.5">{{ error }}</span>
  </label>
</template>