<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" @mousedown.self="$emit('close')">
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] w-full max-w-lg shadow-2xl flex flex-col overflow-hidden max-h-[90vh]">

      <!-- Header -->
      <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
            <MessageSquare class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
          </div>
          <div>
            <h3 class="text-lg font-black text-slate-900 dark:text-white leading-tight">SMS / WhatsApp</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">#{{ docNumber }}</p>
          </div>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
        <button
          v-for="tab in tabs" :key="tab.id"
          @click="activeTab = tab.id"
          :class="['flex-1 py-3 text-sm font-bold transition-colors', activeTab === tab.id ? 'text-emerald-600 dark:text-emerald-400 border-b-2 border-emerald-600' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200']"
        >{{ tab.label }}</button>
      </div>

      <!-- Tab: Send -->
      <div v-if="activeTab === 'send'" class="flex flex-col gap-4 px-6 py-5 overflow-y-auto">

        <!-- Channel -->
        <div>
          <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Canal</label>
          <div class="flex gap-2">
            <button
              v-for="ch in channels" :key="ch.value"
              @click="channel = ch.value"
              :class="['flex-1 py-2 rounded-xl text-sm font-bold border transition-all', channel === ch.value ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/20' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-emerald-400']"
            >{{ ch.label }}</button>
          </div>
        </div>

        <!-- Phone -->
        <div>
          <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Numéro de téléphone</label>
          <input
            v-model="phone"
            type="tel"
            placeholder="+33612345678"
            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <!-- Message -->
        <div>
          <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Message</label>
          <textarea
            v-model="message"
            rows="4"
            class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"
          ></textarea>
          <p class="text-xs text-slate-400 mt-1 text-right">{{ message.length }} caractères</p>
        </div>

        <!-- Error -->
        <div v-if="sendError" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl px-4 py-3 text-sm font-medium">{{ sendError }}</div>

        <!-- Send Button -->
        <button
          @click="doSend"
          :disabled="sending || !phone || !message"
          class="w-full kloxy-btn-success justify-center"
          :class="{ 'opacity-50 cursor-not-allowed': sending || !phone || !message }"
        >
          <Loader2 v-if="sending" class="w-4 h-4 animate-spin mr-2" />
          <Send v-else class="w-4 h-4 mr-2" />
          {{ sending ? 'Envoi...' : 'Envoyer' }}
        </button>
      </div>

      <!-- Tab: Logs -->
      <div v-if="activeTab === 'logs'" class="flex flex-col overflow-hidden">
        <div v-if="logsLoading" class="flex items-center justify-center py-12">
          <Loader2 class="w-6 h-6 animate-spin text-slate-400" />
        </div>

        <div v-else-if="logs.length === 0" class="flex flex-col items-center justify-center py-12 gap-2">
          <MessageSquare class="w-8 h-8 text-slate-300 dark:text-slate-600" />
          <p class="text-sm text-slate-400 dark:text-slate-500">Aucun message envoyé pour ce document</p>
        </div>

        <div v-else class="overflow-y-auto px-4 pb-4 pt-2 flex flex-col gap-2">
          <div
            v-for="(log, idx) in logs" :key="idx"
            class="rounded-2xl p-4 border"
            :class="log.status === 'sent' ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-900/10' : 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10'"
          >
            <div class="flex items-start justify-between gap-2 mb-1">
              <span class="text-xs font-bold" :class="log.status === 'sent' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                {{ log.status === 'sent' ? '✓ Envoyé' : '✗ Erreur' }}
              </span>
              <span class="text-xs text-slate-400 dark:text-slate-500 shrink-0">{{ formatDate(log.date) }}</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 mb-1">{{ log.message }}</p>
            <div class="flex items-center gap-3 mt-1">
              <span class="text-xs text-slate-400">{{ log.phone }}</span>
              <span class="text-xs bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-full px-2 py-0.5">{{ log.provider }}</span>
            </div>
            <p v-if="log.error" class="text-xs text-red-500 mt-1">{{ log.error }}</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { MessageSquare, X, Send, Loader2 } from 'lucide-vue-next';

const props = defineProps({
  docType:     { type: String, required: true }, // 'invoice' | 'quote'
  docId:       { type: Number, required: true },
  docNumber:   { type: String, default: '' },
  clientPhone: { type: String, default: '' },
  defaultMessage: { type: String, default: '' },
});

const emit = defineEmits(['close', 'sent']);

const tabs = [
  { id: 'send', label: '📤 Envoyer' },
  { id: 'logs', label: '📋 Historique' },
];

const activeTab = ref('send');
const channel  = ref('sms');
const phone    = ref(props.clientPhone || '');
const message  = ref(props.defaultMessage || '');
const sending  = ref(false);
const sendError = ref('');

const channels = [
  { value: 'sms',       label: '💬 SMS' },
  { value: 'whatsapp',  label: '📱 WhatsApp' },
  { value: 'both',      label: '🔀 Les deux' },
];

// Logs tab
const logs        = ref([]);
const logsLoading = ref(false);

const fetchLogs = async () => {
  logsLoading.value = true;
  try {
    const res = await fetch(
      `/wp-json/my-easy-compta/v1/sms/logs-for-doc?doc_type=${props.docType}&doc_id=${props.docId}`,
      { headers: { 'X-WP-Nonce': window.myEasyComptaAdmin?.nonce } }
    );
    logs.value = await res.json();
  } catch (e) {
    logs.value = [];
  } finally {
    logsLoading.value = false;
  }
};

watch(activeTab, (tab) => {
  if (tab === 'logs') fetchLogs();
});

const doSend = async () => {
  if (!phone.value || !message.value) return;
  sending.value  = true;
  sendError.value = '';
  try {
    const res = await fetch('/wp-json/my-easy-compta/v1/sms/send-manual', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': window.myEasyComptaAdmin?.nonce,
      },
      body: JSON.stringify({
        phone:    phone.value,
        message:  message.value,
        channel:  channel.value,
        doc_type: props.docType,
        doc_id:   props.docId,
      }),
    });
    const data = await res.json();
    if (data.success) {
      emit('sent');
      // Switch to logs tab after successful send
      activeTab.value = 'logs';
      fetchLogs();
    } else {
      sendError.value = data.message || 'Erreur lors de l\'envoi.';
    }
  } catch (e) {
    sendError.value = e.message || 'Erreur réseau.';
  } finally {
    sending.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr.replace(' ', 'T')).toLocaleString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};
</script>
