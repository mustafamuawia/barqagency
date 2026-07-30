<template>
  <div class="space-y-3">
    <div
      v-for="i in rows"
      :key="i"
      class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 animate-pulse"
    >
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-xl bg-slate-200 shrink-0"></div>
          <div class="space-y-2 min-w-0">
            <div class="h-3.5 bg-slate-200 rounded-full" style="width: 9rem"></div>
            <div class="h-2.5 bg-slate-100 rounded-full" style="width: 6rem"></div>
          </div>
        </div>
        <div class="space-y-2 shrink-0">
          <div class="h-4 bg-slate-200 rounded-full ms-auto" style="width: 4.5rem"></div>
          <div class="h-2.5 bg-slate-100 rounded-full ms-auto" style="width: 3rem"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  rows: { type: Number, default: 3 },
})
</script>
