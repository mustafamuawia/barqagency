<template>
  <Card class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-extrabold text-slate-900">{{ title }}</h2>
      <p v-if="subtitle" class="text-xs text-slate-400 mt-0.5">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </Card>
</template>

<script setup>
import Card from './Card.vue'

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>
