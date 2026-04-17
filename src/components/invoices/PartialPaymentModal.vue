<template>
  <dialog
    :id="modalId"
    class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200"
  >
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-lg shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center">
            <CreditCard class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ modalTitle }}</h3>
            <p class="text-xs font-bold text-slate-400 mt-0.5">
              Reste à payer :
              <span class="text-emerald-600 font-black font-mono">{{ formatAmount(remainingAmount) }}</span>
            </p>
          </div>
        </div>
        <button
          @click="closeModal"
          type="button"
          class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl"
        >
          <X class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="submitForm" class="space-y-5">

        <!-- Amount -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
            Montant du paiement <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              type="number"
              step="0.01"
              :min="0.01"
              :max="remainingAmount"
              v-model="form.amount"
              required
              class="kloxy-input pr-16"
              placeholder="0.00"
            />
            <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 font-black text-sm">{{ currency }}</span>
          </div>
          <!-- Quick fill buttons -->
          <div class="flex gap-2 mt-2">
            <button
              type="button"
              @click="form.amount = remainingAmount"
              class="text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 transition-colors"
            >
              Solde total
            </button>
            <button
              type="button"
              @click="form.amount = (remainingAmount / 2).toFixed(2)"
              class="text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            >
              50 %
            </button>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
            Moyen de paiement <span class="text-red-500">*</span>
          </label>
          <select v-model="form.payment_method_id" class="kloxy-input cursor-pointer" required>
            <option value="" disabled>Choisir un moyen de paiement</option>
            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
              {{ method.method_name }}
            </option>
          </select>
        </div>

        <!-- Payment Date -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
            Date de paiement <span class="text-red-500">*</span>
          </label>
          <VueDatePicker
            v-model="form.payment_date"
            class="w-full"
            input-class-name="kloxy-input"
            :enable-time-picker="false"
            auto-apply
            :format="formatDate"
            locale="fr"
            required
          />
        </div>

        <!-- Notes -->
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
            Notes
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="kloxy-input resize-none"
            placeholder="Référence de virement, chèque n°..."
          ></textarea>
        </div>

        <!-- Error message -->
        <div v-if="errorMsg" class="flex items-center gap-2 p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-sm font-bold">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          {{ errorMsg }}
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
          <button
            type="button"
            @click="closeModal"
            class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="loadingBtn"
            class="bg-emerald-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-emerald-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <CheckCircle2 v-else class="w-4 h-4" />
            Enregistrer le paiement
          </button>
        </div>
      </form>
    </div>

    <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
  </dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, toRefs } from 'vue';
import { X, CreditCard, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import axios from 'axios';

const props = defineProps({
  showModal: Boolean,
  modalId: String,
  modalTitle: { type: String, default: 'Ajouter un paiement' },
  invoiceId: [Number, String],
  remainingAmount: { type: Number, default: 0 },
  currency: { type: String, default: '€' },
  paymentMethods: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'success']);

const { showModal, modalId } = toRefs(props);

const loadingBtn = ref(false);
const errorMsg = ref('');

const form = reactive({
  amount: '',
  payment_method_id: '',
  payment_date: new Date(),
  notes: '',
});

const formatAmount = (val) => {
  if (!val && val !== 0) return '0.00';
  return parseFloat(val).toFixed(2) + ' ' + props.currency;
};

const formatDate = (date) => {
  if (!date) return '';
  const d = date.getDate().toString().padStart(2, '0');
  const m = (date.getMonth() + 1).toString().padStart(2, '0');
  const y = date.getFullYear();
  return `${d}-${m}-${y}`;
};

watch(showModal, (val) => {
  const el = document.getElementById(modalId.value);
  if (!el) return;
  if (val && !el.open) {
    // Reset
    form.amount = '';
    form.payment_method_id = '';
    form.payment_date = new Date();
    form.notes = '';
    errorMsg.value = '';
    el.showModal();
  }
  if (!val && el.open) el.close();
});

const closeModal = () => {
  emit('close');
};

const submitForm = async () => {
  errorMsg.value = '';

  const amount = parseFloat(form.amount);
  if (!amount || amount <= 0) {
    errorMsg.value = 'Le montant doit être supérieur à 0.';
    return;
  }
  if (amount > props.remainingAmount + 0.005) {
    errorMsg.value = `Le montant ne peut pas dépasser le solde restant (${formatAmount(props.remainingAmount)}).`;
    return;
  }
  if (!form.payment_method_id) {
    errorMsg.value = 'Veuillez choisir un moyen de paiement.';
    return;
  }

  // Format date as YYYY-MM-DD for backend
  let paymentDate = '';
  if (form.payment_date instanceof Date) {
    const d = form.payment_date.getDate().toString().padStart(2, '0');
    const m = (form.payment_date.getMonth() + 1).toString().padStart(2, '0');
    const y = form.payment_date.getFullYear();
    paymentDate = `${y}-${m}-${d}`;
  } else {
    paymentDate = form.payment_date;
  }

  loadingBtn.value = true;
  try {
    const res = await axios.post(
      `/wp-json/my-easy-compta/v1/invoices/${props.invoiceId}/add-payment`,
      {
        amount: amount.toFixed(2),
        payment_method_id: form.payment_method_id,
        payment_date: paymentDate,
        notes: form.notes,
      },
      { headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce } }
    );

    if (res.data.success) {
      emit('success');
      closeModal();
    } else {
      errorMsg.value = res.data.message || 'Erreur lors de l\'enregistrement du paiement.';
    }
  } catch (e) {
    const msg = e.response?.data?.message || 'Erreur lors de l\'enregistrement du paiement.';
    errorMsg.value = msg;
  } finally {
    loadingBtn.value = false;
  }
};
</script>

<style scoped>
.kloxy-input {
  @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
</style>
