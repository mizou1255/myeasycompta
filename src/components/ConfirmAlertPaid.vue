<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
      <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-800">
        <!-- Icon -->
        <div class="flex justify-center mb-5">
          <div class="w-16 h-16 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
            <AlertTriangle class="w-8 h-8 text-amber-500" />
          </div>
        </div>

        <!-- Title & message -->
        <h2 class="text-xl font-black text-center text-slate-900 dark:text-white mb-2">{{ title }}</h2>
        <p class="text-center text-slate-500 dark:text-slate-400 font-medium mb-6">{{ message }}</p>

        <!-- Payment method -->
        <div v-if="status === 'paid'" class="mb-6">
          <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">
            {{ translations.payment_method || 'Moyen de paiement' }}
          </label>
          <div class="relative">
            <select
              v-model="selectedPaymentMethod"
              :class="[
                'w-full appearance-none px-4 py-3 pr-10 rounded-2xl border font-bold text-sm bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white transition-colors outline-none cursor-pointer',
                hasPaymentMethodError
                  ? 'border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-400/20'
                  : 'border-slate-200 dark:border-slate-700 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20'
              ]"
            >
              <option value="" class="text-slate-400">{{ translations.select || 'Sélectionner' }}</option>
              <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                {{ method.method_name }}
              </option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>
          <p v-if="hasPaymentMethodError" class="mt-1.5 text-xs font-bold text-rose-500">
            Veuillez sélectionner un moyen de paiement.
          </p>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            @click="onCancel"
            class="flex-1 px-5 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-black text-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
          >
            {{ cancelText }}
          </button>
          <button
            @click="onConfirm"
            class="flex-1 px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm transition-colors shadow-lg shadow-emerald-500/30"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script>
import { AlertTriangle } from 'lucide-vue-next';

export default {
  components: { AlertTriangle },
  props: {
    isVisible: { type: Boolean, default: false },
    showModal: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmation' },
    message: { type: String, default: 'Êtes-vous sûr ?' },
    confirmText: { type: String, default: 'Confirmer' },
    cancelText: { type: String, default: 'Annuler' },
    status: { type: String, default: '' },
  },
  emits: ['confirm', 'cancel'],
  data() {
    return {
      selectedPaymentMethod: '',
      paymentMethods: [],
      hasPaymentMethodError: false,
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin?.easyComptaTranslations || {};
    },
  },
  mounted() {
    this.fetchPaymentMethods();
  },
  methods: {
    async fetchPaymentMethods() {
      try {
        const response = await fetch('/wp-json/my-easy-compta/v1/payments/methods', {
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.myEasyComptaAdmin.nonce,
          },
        });
        if (!response.ok) return;
        const data = await response.json();
        this.paymentMethods = Array.isArray(data) ? data : (data.data || []);
      } catch (e) {
      }
    },
    onConfirm() {
      if (this.status === 'paid' && !this.selectedPaymentMethod) {
        this.hasPaymentMethodError = true;
        return;
      }
      this.hasPaymentMethodError = false;
      this.$emit('confirm', this.selectedPaymentMethod || null);
    },
    onCancel() {
      this.$emit('cancel');
    },
  },
};
</script>
