<template>
  <div class="flex items-center justify-between gap-2">
    <span class="text-[11px] text-slate-500 shrink-0">{{ label }}</span>
    <span class="text-xs font-bold text-slate-800 truncate text-left" :class="mono ? 'font-mono' : ''">
      {{ value || '—' }}
    </span>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: null },
  mono: { type: Boolean, default: false },
})
</script>
