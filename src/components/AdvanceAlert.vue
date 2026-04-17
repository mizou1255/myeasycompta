<template>
  <dialog
    ref="dialogRef"
    class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200"
  >
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-md shadow-2xl border border-slate-100 dark:border-slate-800 relative overflow-hidden">

      <!-- Icon -->
      <div class="w-16 h-16 mx-auto bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
      </div>

      <!-- Title -->
      <h3 class="text-xl font-black text-slate-900 dark:text-white text-center mb-1">{{ title }}</h3>

      <!-- Remaining amount badge -->
      <div class="flex justify-center mb-6">
        <span class="inline-flex items-center gap-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-bold px-4 py-1.5 rounded-full">
          Montant restant : {{ remainingAmount }} {{ currency }}
        </span>
      </div>

      <!-- invoiceSolded -->
      <div v-if="invoiceSolded" class="text-center text-slate-500 dark:text-slate-400 mb-6 text-sm font-medium">
        Ce devis est déjà entièrement soldé.
      </div>

      <template v-if="!invoiceSolded">
        <!-- no_sold: type + valeur -->
        <div v-if="advanceSold === 'no_sold'" class="space-y-4 mb-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Type</label>
            <select
              v-model="selectedType"
              class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-2xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"
              :class="{ 'ring-2 ring-rose-400': !selectedType && showError }"
            >
              <option value="" disabled>Choisir un type</option>
              <option value="percentage">Pourcentage (%)</option>
              <option value="fixed">Montant fixe ({{ currency }})</option>
            </select>
          </div>

          <div v-if="selectedType">
            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Valeur</label>
            <div class="relative">
              <input
                v-model="inputValue"
                type="number"
                min="0"
                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-2xl px-4 py-3 pr-14 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"
                :class="{ 'ring-2 ring-rose-400': !inputValue && showError }"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">
                {{ selectedType === 'percentage' ? '%' : currency }}
              </span>
            </div>
            <p v-if="selectedType === 'percentage' && inputValue && !inputValueExceeds" class="mt-1.5 text-xs text-indigo-500 font-semibold pl-1">
              = {{ calculatedAmount }} {{ currency }}
            </p>
            <p v-if="inputValueExceeds" class="mt-1.5 text-xs text-rose-500 font-semibold pl-1">
              Le montant dépasse le restant disponible.
            </p>
          </div>
        </div>

        <!-- Date -->
        <div class="mb-6">
          <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">{{ translations.due_date || 'Date d\'échéance' }}</label>
          <VueDatePicker
            v-model="due_date"
            :enable-time-picker="false"
            auto-apply
            :format="formattedDate"
            :min-date="new Date()"
            locale="fr"
            required
            :input-class-name="'w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-2xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-400 transition'"
          />
        </div>
      </template>

      <!-- Buttons -->
      <div class="flex flex-col gap-3">
        <button
          @click="onConfirm"
          :disabled="inputValueExceeds || invoiceSolded"
          class="w-full bg-indigo-600 text-white py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 active:scale-95 transition-all shadow-lg shadow-indigo-500/30 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100"
        >
          {{ confirmText }}
        </button>
        <button
          @click="onCancel"
          class="w-full bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
        >
          {{ cancelText }}
        </button>
      </div>
    </div>
    <div class="fixed inset-0 z-[-1] cursor-default" @click="onCancel"></div>
  </dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import VueDatePicker from "@vuepic/vue-datepicker";

const props = defineProps({
  isVisible: { type: Boolean, default: false },
  title: { type: String, default: "Facture d'acompte" },
  message: { type: String, default: "" },
  confirmText: { type: String, default: "Confirmer" },
  cancelText: { type: String, default: "Annuler" },
  totalAmount: { type: [String, Number], required: true },
  currency: { type: String, required: true },
  advanceSold: { type: String, required: true },
  quoteId: { type: Number, required: true },
});

const emit = defineEmits(['confirm', 'cancel']);

const dialogRef = ref(null);
const showError = ref(false);
const selectedType = ref('');
const inputValue = ref('');
const due_date = ref('');
const invoiceSolded = ref(false);
const establishedAdvances = ref([]);

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const formattedDate = (date) => {
  if (!date) return '';
  const d = date.getDate().toString().padStart(2, '0');
  const m = (date.getMonth() + 1).toString().padStart(2, '0');
  return `${d}-${m}-${date.getFullYear()}`;
};

const remainingAmount = computed(() => {
  const total = parseFloat(props.totalAmount) || 0;
  const used = establishedAdvances.value.reduce((sum, a) => sum + (parseFloat(a.advance_amount) || 0), 0);
  return +(total - used).toFixed(2);
});

const calculatedAmount = computed(() => {
  if (selectedType.value === 'percentage') {
    return +((parseFloat(inputValue.value) / 100) * remainingAmount.value).toFixed(2);
  }
  return parseFloat(inputValue.value) || 0;
});

const inputValueExceeds = computed(() => {
  if (remainingAmount.value <= 0) return true;
  if (selectedType.value === 'percentage') return calculatedAmount.value > remainingAmount.value;
  if (selectedType.value === 'fixed') return (parseFloat(inputValue.value) || 0) > remainingAmount.value;
  return false;
});

const fetchEstablishedAdvances = () => {
  fetch(`/wp-json/my-easy-compta/v1/advance/${props.quoteId}`, {
    headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
  })
    .then(r => r.json())
    .then(data => { establishedAdvances.value = Array.isArray(data) ? data : []; })
    .catch(() => { establishedAdvances.value = []; });
};

watch(() => props.isVisible, (val) => {
  if (!dialogRef.value) return;
  if (val && !dialogRef.value.open) {
    fetchEstablishedAdvances();
    dialogRef.value.showModal();
  }
  if (!val && dialogRef.value.open) dialogRef.value.close();
});

onMounted(() => {
  if (props.isVisible && dialogRef.value && !dialogRef.value.open) {
    fetchEstablishedAdvances();
    dialogRef.value.showModal();
  }
});

const onConfirm = () => {
  if (props.advanceSold === 'no_sold') {
    if (!selectedType.value || !inputValue.value || !due_date.value) {
      showError.value = true;
      return;
    }
    emit('confirm', { type: selectedType.value, value: inputValue.value, date: due_date.value });
  } else {
    if (!due_date.value) {
      showError.value = true;
      return;
    }
    emit('confirm', { type: 'fixed', value: remainingAmount.value, date: due_date.value });
  }
};

const onCancel = () => {
  emit('cancel');
  if (dialogRef.value?.open) dialogRef.value.close();
};
</script>
