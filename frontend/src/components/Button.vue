<template>
  <button
    :type="type"
    :disabled="disabled"
    class="font-bold transition flex items-center justify-center gap-2 disabled:opacity-50"
    :class="[variantClasses, sizeClasses, block ? 'w-full' : '']"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: { type: String, default: 'button' },
  variant: { type: String, default: 'dark' }, // dark | amber | secondary | danger-outline
  size: { type: String, default: 'sm' }, // sm | md | lg
  block: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const VARIANT_MAP = {
  dark: 'bg-[#0f172a] hover:bg-[#1e293b] text-white shadow-md',
  amber: 'bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-sm',
  secondary: 'bg-slate-100 hover:bg-slate-200 text-slate-700',
  'danger-outline': 'bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-300',
}

const SIZE_MAP = {
  sm: 'px-4 py-2.5 rounded-xl text-xs',
  md: 'py-3 rounded-xl text-xs',
  lg: 'py-3.5 rounded-2xl text-xs sm:text-sm active:scale-[0.99]',
}

const variantClasses = computed(() => VARIANT_MAP[props.variant] || VARIANT_MAP.dark)
const sizeClasses = computed(() => SIZE_MAP[props.size] || SIZE_MAP.sm)
</script>
