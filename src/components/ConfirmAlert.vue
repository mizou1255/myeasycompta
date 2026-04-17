<template>
  <dialog ref="dialogRef" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-sm text-center shadow-2xl border border-slate-100 dark:border-slate-800 relative overflow-hidden">
          
          <div class="w-20 h-20 mx-auto bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center mb-6">
              <HelpCircle class="w-10 h-10 text-blue-500" />
          </div>

          <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">{{ title }}</h3>
          <p class="text-slate-500 font-medium text-sm leading-relaxed mb-8">{{ message }}</p>

          <div class="flex flex-col gap-3">
              <button @click="onConfirm" class="w-full bg-blue-500 text-white py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 active:scale-95 transition-all shadow-lg shadow-blue-500/30">
                  {{ confirmText }}
              </button>
              <button @click="onCancel" class="w-full bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                  {{ cancelText }}
              </button>
          </div>
      </div>
      <div class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="onCancel"></div>
  </dialog>
</template>

<script setup>
import { HelpCircle } from 'lucide-vue-next';

// Note: Using standard V-If or Prop control in parent, usually 'showModal' prop controls visibility.
// Unlike <dialog>.showModal(), if we want to use v-if/open prop directly we can.
// But matching RemoveAlert structure is safer. I'll stick to 'open' prop bound to showModal.
// However, standard dialog needs .showModal() to be modal (backdrop). 
// Let's use the same pattern as RemoveAlert with watch.

import { toRefs, watch, onMounted } from 'vue';

const props = defineProps({
    showModal: Boolean,
    title: { type: String, default: "Confirmation" },
    message: { type: String, default: "Are you sure?" },
    confirmText: { type: String, default: "Confirm" },
    cancelText: { type: String, default: "Cancel" }
});

const emit = defineEmits(['confirm', 'cancel']);
const { showModal } = toRefs(props);
// Use a ref for the dialog element? Or assume parent handles it? 
// Actually, relying on `v-bind:open` works for visibility but not "modal" behavior (backdrop) in all browsers without `showModal()` method call.
// But standard HTML5 dialog needs .showModal().
// Let's use a ref to the dialog element.

const dialogRef = ref(null);

watch(showModal, (val) => {
    if(dialogRef.value) {
        if(val && !dialogRef.value.open) dialogRef.value.showModal();
        if(!val && dialogRef.value.open) dialogRef.value.close();
    }
});

onMounted(() => {
     if(showModal.value && dialogRef.value && !dialogRef.value.open) dialogRef.value.showModal();
});

const onConfirm = () => emit('confirm');
const onCancel = () => {
    emit('cancel');
    if(dialogRef.value && dialogRef.value.open) dialogRef.value.close();
};

import { ref } from 'vue';
</script>