<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { userService } from '@/services/userService'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const auth = useAuthStore()

const form = ref({ name: auth.user?.name || '' })
const saving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

async function handleSubmit() {
  saving.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    const { data } = await userService.updateProfile(form.value)
    auth.user = data.data
    localStorage.setItem('user', JSON.stringify(data.data))
    successMsg.value = 'Profile updated.'
  } catch {
    errorMsg.value = "Couldn't save your changes."
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-lg mx-auto flex flex-col gap-6">
    <h1 class="text-xl sm:text-2xl font-bold">Profile</h1>

    <NeoCard>
      <div class="flex items-center gap-4 mb-6">
        <div class="neo-raised-compact w-16 h-16 rounded-full flex items-center justify-center text-xl font-semibold text-indigo flex-shrink-0">
          {{ (auth.user?.name || '?').charAt(0).toUpperCase() }}
        </div>
        <div class="min-w-0">
          <p class="font-semibold truncate">{{ auth.user?.name }}</p>
          <p class="text-sm text-ink-soft truncate">{{ auth.user?.email }}</p>
        </div>
      </div>

      <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
        <NeoInput v-model="form.name" label="Full name" />

        <p v-if="successMsg" class="text-sm text-emerald">{{ successMsg }}</p>
        <p v-if="errorMsg" class="text-sm text-coral">{{ errorMsg }}</p>

        <NeoButton type="submit" variant="primary" :disabled="saving">
          {{ saving ? 'Saving…' : 'Save changes' }}
        </NeoButton>
      </form>
    </NeoCard>

    <p class="text-sm text-ink-soft text-center">
      Want to change your password?
      <router-link :to="{ name: 'settings' }" class="text-indigo font-semibold">Go to settings</router-link>
    </p>
  </div>
</template>