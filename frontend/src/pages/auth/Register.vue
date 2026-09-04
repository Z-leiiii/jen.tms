<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const submitting = ref(false)

async function handleSubmit() {
  submitting.value = true
  try {
    await auth.register(form.value)
    router.push({ name: 'dashboard' })
  } catch {
    // auth.error is already set and rendered below
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <h1 class="text-xl font-bold mb-1">Create your account</h1>
  <p class="text-sm text-ink-soft mb-6">Start organising your projects and tasks.</p>

  <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
    <NeoInput v-model="form.name" label="Name" placeholder="Jane Dela Cruz" autocomplete="name" />
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
      autocomplete="new-password"
    />
    <NeoInput
      v-model="form.password_confirmation"
      type="password"
      label="Confirm password"
      placeholder="••••••••"
      autocomplete="new-password"
    />

    <p v-if="auth.error" class="text-sm text-coral">{{ auth.error }}</p>

    <NeoButton type="submit" variant="primary" block :disabled="submitting">
      {{ submitting ? 'Creating account…' : 'Create account' }}
    </NeoButton>
  </form>

  <p class="text-sm text-ink-soft text-center mt-6">
    Already have an account?
    <router-link :to="{ name: 'login' }" class="text-indigo font-semibold">Log in</router-link>
  </p>
</template>
