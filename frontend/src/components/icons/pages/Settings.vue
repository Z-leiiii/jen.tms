<script setup>
import { ref } from 'vue'
import { userService } from '@/services/userService'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const form = ref({ current_password: '', password: '', password_confirmation: '' })
const saving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

async function handleSubmit() {
  saving.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    await userService.updatePassword(form.value)
    successMsg.value = 'Password updated.'
    form.value = { current_password: '', password: '', password_confirmation: '' }
  } catch (err) {
    errorMsg.value = err.response?.data?.errors?.current_password?.[0] || "Couldn't update your password."
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-lg mx-auto flex flex-col gap-6">
    <h1 class="text-xl sm:text-2xl font-bold">Settings</h1>

    <NeoCard>
      <p class="font-semibold mb-1">Change password</p>
      <p class="text-sm text-ink-soft mb-5">Choose a strong password you're not using anywhere else.</p>

      <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
        <NeoInput
          v-model="form.current_password"
          type="password"
          label="Current password"
          autocomplete="current-password"
        />
        <NeoInput
          v-model="form.password"
          type="password"
          label="New password"
          autocomplete="new-password"
        />
        <NeoInput
          v-model="form.password_confirmation"
          type="password"
          label="Confirm new password"
          autocomplete="new-password"
        />

        <p v-if="successMsg" class="text-sm text-emerald">{{ successMsg }}</p>
        <p v-if="errorMsg" class="text-sm text-coral">{{ errorMsg }}</p>

        <NeoButton type="submit" variant="primary" :disabled="saving">
          {{ saving ? 'Updating…' : 'Update password' }}
        </NeoButton>
      </form>
    </NeoCard>
  </div>
</template>