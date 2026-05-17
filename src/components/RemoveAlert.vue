<template>
  <dialog :id="modalId" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-sm text-center shadow-2xl border border-slate-100 dark:border-slate-800 relative overflow-hidden">
          
          <div class="w-20 h-20 mx-auto bg-rose-50 dark:bg-rose-900/20 rounded-full flex items-center justify-center mb-6">
              <AlertTriangle class="w-10 h-10 text-rose-500" />
          </div>

          <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">{{ title }}</h3>
          <p class="text-slate-500 font-medium text-sm leading-relaxed mb-8">{{ message }}</p>

          <div class="flex flex-col gap-3">
              <button @click="onConfirm" class="w-full bg-rose-500 text-white py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-rose-600 active:scale-95 transition-all shadow-lg shadow-rose-500/30">
                  {{ confirmText }}
              </button>
              <button @click="onCancel" class="w-full bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                  {{ cancelText }}
              </button>
          </div>
      </div>
      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="onCancel"></form>
  </dialog>
</template>

<script setup>
import { toRefs, watch } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    modalId: String,
    showModal: Boolean,
    title: { type: String, default: "Confirmation" },
    message: { type: String, default: "Vous êtes sûr ?" },
    confirmText: { type: String, default: "Supprimer" },
    cancelText: { type: String, default: "Annuler" }
});

const emit = defineEmits(['confirm', 'cancel']);
const { showModal, modalId } = toRefs(props);

watch(showModal, (val) => {
    const el = document.getElementById(modalId.value);
    if(el) {
        if(val && !el.open) el.showModal();
        if(!val && el.open) el.close();
    }
});

const onConfirm = () => emit('confirm');
const onCancel = () => {
    emit('cancel');
    const el = document.getElementById(modalId.value);
    if(el && el.open) el.close();
};
</script>