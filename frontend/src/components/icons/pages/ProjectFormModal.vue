<script setup>
import { ref, watch } from 'vue'
import { useProjectStore } from '@/stores/projectStore'
import NeoModal from '@/components/ui/NeoModal.vue'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'created'])

const projectStore = useProjectStore()

const colors = ['#5B6EF5', '#2FB380', '#F2994A', '#EF6461', '#9B6EF0']
const form = ref({ name: '', description: '', color: colors[0] })
const submitting = ref(false)
const errorMsg = ref('')

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      form.value = { name: '', description: '', color: colors[0] }
      errorMsg.value = ''
    }
  }
)

async function handleSubmit() {
  submitting.value = true
  errorMsg.value = ''
  try {
    const project = await projectStore.createProject(form.value)
    emit('created', project)
    emit('close')
  } catch {
    errorMsg.value = "Couldn't create the project. Check the form and try again."
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <NeoModal :open="open" title="New project" @close="$emit('close')">
    <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
      <NeoInput v-model="form.name" label="Project name" placeholder="e.g. Chem 201 group lab" />
      <NeoInput v-model="form.description" label="Description (optional)" placeholder="What's this project for?" />

      <div>
        <span class="block text-xs font-semibold text-ink-soft mb-2">Color</span>
        <div class="flex gap-3">
          <button
            v-for="c in colors"
            :key="c"
            type="button"
            class="w-8 h-8 rounded-full flex-shrink-0 transition-shadow"
            :class="form.color === c ? 'shadow-neo-inset-sm' : 'shadow-neo-xs'"
            :style="{ backgroundColor: c }"
            :aria-label="`Choose color ${c}`"
            @click="form.color = c"
          />
        </div>
      </div>

      <p v-if="errorMsg" class="text-sm text-coral">{{ errorMsg }}</p>

      <NeoButton type="submit" variant="primary" block :disabled="submitting || !form.name">
        {{ submitting ? 'Creating…' : 'Create project' }}
      </NeoButton>
    </form>
  </NeoModal>
</template>