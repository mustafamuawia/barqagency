<template>
  <div class="overflow-x-auto rounded-2xl border border-slate-200">
    <table class="w-full text-right text-xs">
      <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
        <tr>
          <th v-for="column in columns" :key="column" class="p-3 font-semibold">{{ column }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <slot />
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, required: true },
})
</script>
