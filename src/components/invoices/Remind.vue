<template>
  <dialog ref="dialogRef" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <h3 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
          <Bell class="w-6 h-6 text-amber-500" />
          {{ translations.remind_invoice || 'Relance de facture' }}
        </h3>
        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
          <X class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Client Email -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.client || 'Client' }}</label>
          <input type="text" :value="client?.email" disabled class="kloxy-input opacity-60 cursor-not-allowed" />
        </div>

        <!-- Subject -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.email_subject || 'Objet' }}</label>
          <input type="text" v-model="formData.subject" class="kloxy-input" />
        </div>

        <!-- Message -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.email_content || 'Message' }}</label>
          <div class="kloxy-editor-wrapper">
            <vue-editor v-model="formData.message" :editorToolbar="toolbarOptions" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
          <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            {{ translations.cancel || 'Annuler' }}
          </button>
          <button type="submit" :disabled="loadingBtn" class="bg-amber-500 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-600 active:scale-95 transition-all shadow-lg shadow-amber-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <Bell v-else class="w-4 h-4" />
            {{ translations.send || 'Envoyer' }}
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
  </dialog>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue';
import { VueEditor } from "vue3-editor";
import { Bell, X } from 'lucide-vue-next';

const props = defineProps({
  showModal: Boolean,
  client: Object,
  invoiceId: [Number, String],
  subject: String,
  content: String,
});

const emit = defineEmits(['close', 'success']);

const dialogRef = ref(null);
const loadingBtn = ref(false);
const formData = reactive({ subject: '', message: '' });
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  ['clean'],
];

watch(() => props.showModal, (val) => {
  if (!dialogRef.value) return;
  if (val && !dialogRef.value.open) dialogRef.value.showModal();
  if (!val && dialogRef.value.open) dialogRef.value.close();
});

watch([() => props.subject, () => props.content], ([sub, cont]) => {
  formData.subject = sub || '';
  formData.message = cont || '';
}, { immediate: true });

onMounted(() => {
  if (props.showModal && dialogRef.value && !dialogRef.value.open) dialogRef.value.showModal();
});

const closeModal = () => {
  emit('close');
  if (dialogRef.value?.open) dialogRef.value.close();
};

const submitForm = async () => {
  loadingBtn.value = true;
  try {
    const res = await fetch('/wp-json/my-easy-compta/v1/emails/send-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
      body: JSON.stringify({
        type: 'remind',
        id: props.invoiceId,
        client_email: props.client?.email,
        email_subject: formData.subject,
        email_message: formData.message,
      }),
    });
    const data = await res.json();
    if (res.ok) {
      emit('success', data.message);
      closeModal();
    } else {
      emit('success', data.message || 'Erreur envoi');
    }
  } catch (e) {
  } finally {
    loadingBtn.value = false;
  }
};
</script>
