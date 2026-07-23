<template>
  <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between text-xs text-slate-500">
    <span>صفحة {{ meta.current_page }} من {{ meta.last_page }} ({{ meta.total }} سجل)</span>
    <div class="flex gap-2">
      <button
        :disabled="meta.current_page <= 1"
        @click="$emit('change', meta.current_page - 1)"
        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 font-bold transition"
      >
        السابق
      </button>
      <button
        :disabled="meta.current_page >= meta.last_page"
        @click="$emit('change', meta.current_page + 1)"
        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-40 font-bold transition"
      >
        التالي
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  meta: { type: Object, default: null },
})
defineEmits(['change'])
</script>
