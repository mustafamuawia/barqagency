<template>
  <div class="relative" ref="rootEl">
    <input
      v-model="query"
      type="text"
      :required="required"
      :placeholder="placeholder"
      autocomplete="off"
      @focus="open = true"
      @input="open = true; highlighted = 0"
      @keydown.down.prevent="move(1)"
      @keydown.up.prevent="move(-1)"
      @keydown.enter.prevent="selectHighlighted"
      @keydown.esc="open = false"
      :class="inputClass"
    />
    <ul v-if="open && results.length"
      class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto text-xs">
      <li v-for="(airport, i) in results" :key="airport.iata"
        @mousedown.prevent="select(airport)"
        :class="['px-3 py-2 cursor-pointer flex items-center justify-between gap-2', i === highlighted ? 'bg-amber-50' : 'hover:bg-slate-50']">
        <span class="font-semibold text-slate-800 truncate">
          {{ airport.name }}<span class="text-slate-400 font-normal"> — {{ airport.city }}</span>
        </span>
        <span class="font-mono font-bold text-amber-600 shrink-0">{{ airport.iata }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import airportList from 'airport-codes/airports.json'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'اكتب رمز أو اسم المطار' },
  required: { type: Boolean, default: false },
  dense: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const rootEl = ref(null)
const open = ref(false)
const highlighted = ref(0)
const query = ref(props.modelValue)

watch(() => props.modelValue, (value) => { if (value !== query.value) query.value = value })
watch(query, (value) => emit('update:modelValue', value))

// Only airports with a real IATA code are useful for ticketing/search purposes.
const airports = airportList.filter((a) => a.iata && a.iata.trim())

const results = computed(() => {
  const term = query.value.trim().toLowerCase()
  if (!term) return []
  return airports
    .filter((a) =>
      a.iata.toLowerCase().startsWith(term) ||
      a.name.toLowerCase().includes(term) ||
      a.city.toLowerCase().includes(term)
    )
    .slice(0, 8)
})

const inputClass = computed(() => props.dense
  ? 'w-full border border-slate-200 rounded-xl px-3 py-2 text-[11px] bg-white focus:outline-none focus:ring-2 focus:ring-amber-400'
  : 'w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400')

const move = (delta) => {
  if (!results.value.length) return
  highlighted.value = (highlighted.value + delta + results.value.length) % results.value.length
}

const select = (airport) => {
  query.value = `${airport.name} (${airport.iata})`
  open.value = false
  emit('select', airport)
}

const selectHighlighted = () => {
  if (open.value && results.value[highlighted.value]) select(results.value[highlighted.value])
}

const onClickOutside = (event) => {
  if (rootEl.value && !rootEl.value.contains(event.target)) open.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>
