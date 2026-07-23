<template>
  <Card>
    <div class="flex items-center gap-2 mb-3">
      <div class="w-8 h-8 rounded-xl flex items-center justify-center" :class="iconBg">
        <component :is="icon" class="w-4 h-4" :class="iconColor" />
      </div>
      <p class="text-xs text-slate-500 font-medium">{{ label }}</p>
    </div>
    <p class="font-black mt-1" :class="[valueColor, large ? 'text-3xl' : 'text-2xl', mono ? 'font-mono' : '']">{{ value }}</p>
    <p v-if="caption" class="text-[10px] text-slate-400 mt-1">{{ caption }}</p>
  </Card>
</template>

<script setup>
import Card from './Card.vue'

defineProps({
  icon: { type: [Object, Function], required: true },
  iconBg: { type: String, default: 'bg-slate-100' },
  iconColor: { type: String, default: 'text-slate-600' },
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  caption: { type: String, default: '' },
  valueColor: { type: String, default: 'text-slate-900' },
  large: { type: Boolean, default: false },
  mono: { type: Boolean, default: true },
})
</script>
