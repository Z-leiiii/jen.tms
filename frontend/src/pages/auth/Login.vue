<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ email: '', password: '' })
const submitting = ref(false)

async function handleSubmit() {
  submitting.value = true
  try {
    await auth.login(form.value)
    router.push({ name: 'dashboard' })
  } catch {
    // auth.error is already set and rendered below
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <h1 class="text-xl font-bold mb-1">Welcome back</h1>
  <p class="text-sm text-ink-soft mb-6">Log in to keep track of your work.</p>

  <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
    <NeoInput
      v-model="form.email"
      type="email"
      label="Email"
      placeholder="name@university.edu"
      autocomplete="email"
    />
    <NeoInput
      v-model="form.password"
      type="password"
      label="Password"
      placeholder="••••••••"
      autocomplete="current-password"
    />

    <p v-if="auth.error" class="text-sm text-coral">{{ auth.error }}</p>

    <NeoButton type="submit" variant="primary" block :disabled="submitting">
      {{ submitting ? 'Logging in…' : 'Log in' }}
    </NeoButton>
  </form>

  <p class="text-sm text-ink-soft text-center mt-6">
    New here?
    <router-link :to="{ name: 'register' }" class="text-indigo font-semibold">Create an account</router-link>
  </p>
</template>