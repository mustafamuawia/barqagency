<template>
  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="colorClasses">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  color: { type: String, default: 'slate' },
})

const COLOR_MAP = {
  amber: 'bg-amber-100 text-amber-800',
  emerald: 'bg-emerald-100 text-emerald-800',
  red: 'bg-red-100 text-red-800',
  blue: 'bg-blue-100 text-blue-800',
  purple: 'bg-purple-100 text-purple-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  slate: 'bg-slate-100 text-slate-600',
}

const colorClasses = computed(() => COLOR_MAP[props.color] || COLOR_MAP.slate)
</script>
