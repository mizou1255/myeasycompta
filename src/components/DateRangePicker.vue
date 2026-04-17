<template>
  <div class="flex items-center gap-2 flex-1 min-w-[260px] kloxy-input !py-2 !px-4 cursor-pointer">
    <CalendarDays class="w-4 h-4 text-slate-400 flex-shrink-0" />
    <input
      type="date"
      :value="from"
      @change="$emit('update:from', $event.target.value); $emit('change')"
      class="!bg-transparent text-sm font-bold text-slate-700 dark:text-slate-300 focus:!outline-none !border-none !shadow-none w-full min-w-0"
      :placeholder="labelFrom"
    />
    <span class="text-slate-300 dark:text-slate-600 flex-shrink-0 font-bold text-xs">→</span>
    <input
      type="date"
      :value="to"
      @change="$emit('update:to', $event.target.value); $emit('change')"
      class="!bg-transparent text-sm font-bold text-slate-700 dark:text-slate-300 focus:!outline-none !border-none !shadow-none w-full min-w-0"
      :placeholder="labelTo"
    />
  </div>
</template>

<script setup>
import { CalendarDays } from 'lucide-vue-next';

defineProps({
  from: { type: String, default: '' },
  to:   { type: String, default: '' },
  labelFrom: { type: String, default: 'Du...' },
  labelTo:   { type: String, default: 'Au...' },
});

defineEmits(['update:from', 'update:to', 'change']);
</script>
