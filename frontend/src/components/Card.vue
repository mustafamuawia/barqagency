<template>
  <div class="bg-white rounded-3xl shadow-sm border border-slate-200" :class="padded ? 'p-6' : ''">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  padded: { type: Boolean, default: true },
})
</script>
