<script setup>
import { computed, ref } from 'vue'
import api from '../services/api'

const subject = ref('')
const selectedFile = ref(null)
const isGenerating = ref(false)
const generated = ref(false)
const current = ref(0)
const selectedAnswer = ref(null)
const score = ref(0)
const revealed = ref(false)

const starterSubjects = ['Statics', 'Data structures', 'Thermodynamics', 'Calculus II']
const questions = ref([])

const activeQuestion = computed(() => questions.value[current.value])
const percent = computed(() => questions.value.length ? Math.round((current.value / questions.value.length) * 100) : 0)
const resultMessage = computed(() => score.value === questions.value.length ? 'Perfect run. Your brain is running hot.' : `You got ${score.value} of ${questions.value.length}. One more lap and it sticks.`)

function chooseSubject(value) {
  subject.value = value
}

function onFile(event) {
  selectedFile.value = event.target.files?.[0] || null
  if (selectedFile.value) subject.value = selectedFile.value.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ')
}

async function generate() {
  isGenerating.value = true
  
  try {
    const response = await api.post('/quiz/generate', {
      subject: subject.value,
      file_content: null // Can be enhanced later to process uploaded files
    })
    
    questions.value = response.data.questions
    current.value = 0
    score.value = 0
    selectedAnswer.value = null
    revealed.value = false
    generated.value = true
  } catch (error) {
    console.error('Failed to generate quiz:', error)
    alert('Failed to generate quiz. Please try again.')
  } finally {
    isGenerating.value = false
  }
}

function answer(index) {
  if (selectedAnswer.value !== null) return
  selectedAnswer.value = index
  revealed.value = true
  if (index === activeQuestion.value.correct) score.value += 1
}

function next() {
  if (current.value < questions.value.length - 1) {
    current.value += 1
    selectedAnswer.value = null
    revealed.value = false
  } else {
    current.value = questions.value.length
  }
}

function reset() {
  generated.value = false
  selectedFile.value = null
  subject.value = ''
}

</script>

<template>
  <div class="mx-auto flex max-w-5xl flex-col gap-5 pb-6">
    <header>
      <p class="text-xs font-bold uppercase tracking-[0.18em] text-coral">Study lab · zero busywork</p>
      <div class="mt-1 flex items-end justify-between gap-3">
        <div><h1 class="font-display text-3xl font-bold">Turn notes into momentum.</h1><p class="mt-2 max-w-xl text-sm text-ink-soft">Drop in a lecture, choose a subject, and get a quick-fire quiz you can actually finish between classes.</p></div>
        <span class="hidden text-5xl sm:block" aria-hidden="true">🧠</span>
      </div>
    </header>

    <template v-if="!generated">
      <section class="neo-raised border-2 border-dashed border-indigo/30 p-5 sm:p-8">
        <div class="flex flex-col items-center justify-center text-center">
          <div class="neo-inset mb-4 flex h-16 w-16 items-center justify-center rounded-full text-3xl">📎</div>
          <h2 class="text-xl font-bold">Bring your study material</h2>
          <p class="mt-1 max-w-md text-sm text-ink-soft">PDF, TXT, or a pasted topic. This demo turns the subject into a quiz locally, ready for a Supabase file pipeline later.</p>
          <label class="neo-btn mt-5 cursor-pointer text-indigo">{{ selectedFile ? 'Change file' : 'Choose a file' }}<input class="hidden" type="file" accept=".pdf,.txt,.md" @change="onFile" /></label>
          <p v-if="selectedFile" class="mt-3 max-w-full truncate text-xs font-semibold text-emerald">{{ selectedFile.name }} ready</p>
        </div>
      </section>

      <section class="neo-raised p-5">
        <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-ink-soft">Or pick your battlefield</p><h2 class="mt-1 text-xl font-bold">What are we learning?</h2></div><span class="text-2xl">⚙</span></div>
        <input v-model="subject" class="neo-field mt-4" placeholder="e.g. Signals and systems" />
        <div class="mt-3 flex flex-wrap gap-2"><button v-for="item in starterSubjects" :key="item" class="neo-btn !min-h-10 !px-3 !text-xs" :class="subject === item ? 'text-indigo' : 'text-ink-soft'" @click="chooseSubject(item)">{{ item }}</button></div>
        <button class="neo-btn mt-5 w-full !bg-indigo !text-white !shadow-none sm:w-auto" :disabled="!subject.trim() || isGenerating" @click="generate">{{ isGenerating ? 'Generating questions' : '⚡ Generate 30 questions' }}</button>
      </section>
    </template>

    <template v-else>
      <section v-if="current < questions.length" class="neo-raised p-5 sm:p-8">
        <div class="mb-6 flex items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo">{{ subject }} · quick fire</p><h2 class="mt-1 font-display text-2xl font-bold">Question {{ current + 1 }} of {{ questions.length }}</h2></div><span class="font-mono text-sm text-ink-soft">{{ percent }}%</span></div>
        <div class="mb-6 h-2 rounded-full bg-indigo-soft"><div class="h-full rounded-full bg-indigo transition-all" :style="{ width: `${((current + 1) / questions.length) * 100}%` }" /></div>
        <p class="max-w-2xl text-xl font-semibold leading-snug sm:text-2xl">{{ activeQuestion.prompt }}</p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2"><button v-for="(answerText, index) in activeQuestion.answers" :key="answerText" class="neo-btn min-h-[58px] justify-start px-4 text-left" :class="selectedAnswer !== null && index === activeQuestion.correct ? '!bg-emerald-soft !text-emerald' : selectedAnswer === index ? '!bg-coral-soft !text-coral' : 'text-ink-soft'" @click="answer(index)"><span class="font-mono text-xs opacity-60">0{{ index + 1 }}</span>{{ answerText }}</button></div>
        <div v-if="revealed" class="neo-inset mt-6 p-4"><p class="text-sm font-semibold">{{ selectedAnswer === activeQuestion.correct ? 'Correct. Nice catch.' : 'Not quite, but now you know.' }}</p><p class="mt-1 text-sm text-ink-soft">{{ activeQuestion.why }}</p><button class="neo-btn mt-4 text-indigo" @click="next">{{ current === questions.length - 1 ? 'See my result' : 'Next question →' }}</button></div>
      </section>
      <section v-else class="neo-raised bg-emerald-soft p-7 text-center"><span class="text-5xl">🏁</span><p class="mt-4 text-xs font-bold uppercase tracking-widest text-emerald">Quiz complete</p><h2 class="mt-1 font-display text-3xl font-bold">{{ resultMessage }}</h2><p class="mt-2 text-sm text-ink-soft">That was {{ subject }}. The next rep is where the magic gets sticky.</p><button class="neo-btn mt-6 text-indigo" @click="generate">Run it again</button><button class="neo-btn ml-2 mt-6 text-ink-soft" @click="reset">New material</button></section>
    </template>
  </div>
</template>
