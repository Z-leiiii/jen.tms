<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const router = useRouter()
const auth = useAuthStore()
const sprintRunning = ref(false)
const sprintSeconds = ref(25 * 60)
let sprintTimer

const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Engineer')
const sprintLabel = computed(() => {
  const minutes = Math.floor(sprintSeconds.value / 60).toString().padStart(2, '0')
  const seconds = (sprintSeconds.value % 60).toString().padStart(2, '0')
  return `${minutes}:${seconds}`
})

const tasks = ref([
  { title: 'Finish circuits lab report', meta: 'Due today · ECE 204', tag: 'urgent', done: false },
  { title: 'Review thermodynamics notes', meta: '45 min study block', tag: 'focus', done: false },
  { title: 'Push group project prototype', meta: 'Due tomorrow · Design team', tag: 'team', done: true },
])

function toggleSprint() {
  sprintRunning.value = !sprintRunning.value
  if (sprintRunning.value) {
    sprintTimer = setInterval(() => {
      if (sprintSeconds.value > 0) sprintSeconds.value -= 1
      else toggleSprint()
    }, 1000)
  } else {
    clearInterval(sprintTimer)
  }
}

function openStudy() { router.push({ name: 'study' }) }
</script>

<template>
  <div class="mx-auto flex max-w-6xl flex-col gap-5 pb-6">
    <section class="neo-raised overflow-hidden bg-indigo p-5 text-white sm:p-7">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-white/70">Thursday · 04 September</p>
          <h1 class="font-display text-3xl font-bold leading-tight sm:text-4xl">Make today<br />ridiculously productive, {{ firstName }}.</h1>
          <p class="mt-3 max-w-md text-sm text-white/75">One focused sprint, one tiny win, then momentum does the heavy lifting.</p>
        </div>
        <span class="hidden text-5xl sm:block" aria-hidden="true">✦</span>
      </div>
      <div class="mt-6 flex flex-wrap gap-3">
        <NeoButton class="!bg-white !text-indigo !shadow-none" @click="openStudy">🧠 Make a quiz</NeoButton>
        <NeoButton class="!bg-indigo-dark !text-white !shadow-none" @click="toggleSprint">{{ sprintRunning ? 'Pause sprint' : 'Start 25 min sprint' }}</NeoButton>
      </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-[1.35fr_1fr]">
      <NeoCard>
        <div class="mb-4 flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-ink-soft">Your launch queue</p><h2 class="mt-1 text-xl font-bold">Next up</h2></div><span class="text-2xl">🚀</span></div>
        <div class="flex flex-col gap-2">
          <label v-for="task in tasks" :key="task.title" class="neo-inset-sm flex min-h-touch cursor-pointer items-center gap-3 px-3 py-2 transition-opacity" :class="task.done ? 'opacity-50' : ''">
            <input v-model="task.done" type="checkbox" class="h-5 w-5 accent-indigo" />
            <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold" :class="task.done ? 'line-through' : ''">{{ task.title }}</span><span class="block truncate text-xs text-ink-soft">{{ task.meta }}</span></span>
            <span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase" :class="task.tag === 'urgent' ? 'bg-coral-soft text-coral' : task.tag === 'focus' ? 'bg-indigo-soft text-indigo' : 'bg-emerald-soft text-emerald'">{{ task.tag }}</span>
          </label>
        </div>
      </NeoCard>

      <NeoCard class="flex flex-col justify-between">
        <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-ink-soft">Focus reactor</p><h2 class="mt-1 text-xl font-bold">{{ sprintLabel }}</h2></div><span class="text-2xl">⏱</span></div>
        <div class="my-5 h-3 overflow-hidden rounded-full bg-indigo-soft"><div class="h-full rounded-full bg-indigo transition-all" :style="{ width: `${(sprintSeconds / 1500) * 100}%` }" /></div>
        <button class="neo-btn w-full text-indigo" @click="toggleSprint">{{ sprintRunning ? 'Pause the reactor' : 'Ignite focus mode' }}</button>
      </NeoCard>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
      <NeoCard compact><p class="text-xs text-ink-soft">Study streak</p><p class="mt-1 font-display text-2xl font-bold">7 <span class="text-base">days</span></p></NeoCard>
      <NeoCard compact><p class="text-xs text-ink-soft">Quiz accuracy</p><p class="mt-1 font-display text-2xl font-bold text-emerald">86%</p></NeoCard>
      <NeoCard compact><p class="text-xs text-ink-soft">Due this week</p><p class="mt-1 font-display text-2xl font-bold text-amber">12</p></NeoCard>
      <NeoCard compact><p class="text-xs text-ink-soft">Team pulse</p><p class="mt-1 font-display text-2xl font-bold text-coral">Good</p></NeoCard>
    </div>
  </div>
</template>