<template>
  <dialog :open="showModal" :class="['fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 w-full h-full border-none m-0 max-w-none max-h-none', showModal ? 'block' : 'hidden']">
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-full max-w-3xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative max-h-[90vh] overflow-y-auto">
      
      <!-- Header -->
      <div class="flex items-center justify-between mb-8 sticky top-0 bg-white dark:bg-slate-900 z-10 pb-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-4">
              <div v-if="client" class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center text-purple-600 dark:text-purple-400 font-black text-xl">
                  {{ getInitials(client.company_name) }}
              </div>
              <div>
                  <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                      {{ modalTitle }}
                  </h3>
                  <p v-if="client" class="text-slate-500 font-bold text-sm">{{ client.company_name }}</p>
              </div>
          </div>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
              <X class="w-6 h-6" />
          </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div v-for="n in 6" :key="n" class="space-y-3">
              <div class="h-3 w-24 bg-slate-100 dark:bg-slate-800 rounded-full animate-pulse"></div>
              <div class="h-10 w-full bg-slate-50 dark:bg-slate-900 rounded-2xl animate-pulse"></div>
          </div>
      </div>

      <!-- Content -->
      <div v-else-if="client" class="space-y-10">
          
          <!-- Basic Info Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
              
              <!-- Legal Info Section -->
              <div v-if="client.siret_number != 0 || client.siren_number != 0 || client.tax_number != 0" class="col-span-full pb-4 border-b border-slate-50 dark:border-slate-800">
                  <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2">
                      <ShieldCheck class="w-4 h-4" /> Informations Légales
                  </h4>
                  <div class="flex flex-wrap gap-8">
                      <div v-if="client.siret_number != 0 && addon_siret_active" class="space-y-1">
                          <p class="text-[10px] font-black uppercase text-slate-400">{{ translations.siret }}</p>
                          <p class="font-mono text-sm text-slate-900 dark:text-white">{{ client.siret_number }}</p>
                      </div>
                      <div v-if="client.siren_number != 0" class="space-y-1">
                          <p class="text-[10px] font-black uppercase text-slate-400">{{ translations.siren }}</p>
                          <p class="font-mono text-sm text-slate-900 dark:text-white">{{ client.siren_number }}</p>
                      </div>
                      <div v-if="client.tax_number != 0" class="space-y-1">
                          <p class="text-[10px] font-black uppercase text-slate-400">{{ translations.tax_number }}</p>
                          <p class="font-mono text-sm text-slate-900 dark:text-white">{{ client.tax_number }}</p>
                      </div>
                  </div>
              </div>

              <!-- Main Fields -->
              <div v-for="(field, key) in displayFields" :key="key" class="space-y-2 group">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2 ml-1">
                      <component :is="field.icon" class="w-3.5 h-3.5 text-slate-300 group-hover:text-purple-500 transition-colors" />
                      {{ field.label }}
                  </label>
                  <div class="px-5 py-4 bg-slate-50/50 dark:bg-slate-950/50 rounded-2xl border border-transparent hover:border-slate-100 dark:hover:border-slate-800 transition-all">
                      <a v-if="field.type === 'url' && client[key]" :href="client[key]" target="_blank" class="text-purple-600 dark:text-purple-400 font-bold text-sm hover:underline">
                          {{ client[key] }}
                      </a>
                      <a v-else-if="field.type === 'tel' && client[key]" :href="'tel:' + client[key]" class="text-slate-900 dark:text-white font-bold text-sm hover:text-purple-600 transition-colors">
                          {{ client[key] }}
                      </a>
                      <p v-else class="text-slate-900 dark:text-white font-bold text-sm">
                          {{ client[key] || '-' }}
                      </p>
                  </div>
              </div>

              <!-- Currency -->
              <div class="space-y-2">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2 ml-1">
                      <Banknote class="w-3.5 h-3.5 text-slate-300" />
                      {{ translations.currency }}
                  </label>
                  <div class="px-5 py-4 bg-purple-50/30 dark:bg-purple-900/10 rounded-2xl border border-purple-100/20">
                      <p class="text-purple-700 dark:text-purple-300 font-black text-sm">
                          {{ getCurrencyOption(client.currency_id) }}
                      </p>
                  </div>
              </div>
          </div>

              <!-- Financial Summary -->
          <div v-if="client._financials" class="grid grid-cols-3 gap-4">
              <div class="bg-slate-50 dark:bg-slate-950/50 rounded-2xl p-5 text-center border border-slate-100 dark:border-slate-800">
                  <TrendingUp class="w-5 h-5 mx-auto mb-2 text-slate-400" />
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ translations.total_invoiced || 'Facturé' }}</p>
                  <p class="text-base font-black text-slate-900 dark:text-white">{{ formatAmount(client._financials.total_invoiced, client._financials.symbol) }}</p>
              </div>
              <div class="bg-green-50 dark:bg-green-900/10 rounded-2xl p-5 text-center border border-green-100/30 dark:border-green-900/20">
                  <CheckCircle2 class="w-5 h-5 mx-auto mb-2 text-green-500" />
                  <p class="text-[10px] font-black uppercase tracking-widest text-green-500 mb-1">{{ translations.total_paid || 'Payé' }}</p>
                  <p class="text-base font-black text-green-700 dark:text-green-400">{{ formatAmount(client._financials.total_paid, client._financials.symbol) }}</p>
              </div>
              <div class="bg-amber-50 dark:bg-amber-900/10 rounded-2xl p-5 text-center border border-amber-100/30 dark:border-amber-900/20">
                  <AlertCircle class="w-5 h-5 mx-auto mb-2 text-amber-500" />
                  <p class="text-[10px] font-black uppercase tracking-widest text-amber-500 mb-1">{{ translations.outstanding || 'Dû' }}</p>
                  <p class="text-base font-black text-amber-700 dark:text-amber-400">{{ formatAmount(client._financials.outstanding, client._financials.symbol) }}</p>
              </div>
          </div>

          <!-- Payment Behavior -->
          <div v-if="client._payment_behavior && client._payment_behavior.total_invoices > 0" class="p-5 rounded-2xl border"
               :class="paymentBehavior.color === 'green' ? 'bg-green-50 dark:bg-green-900/10 border-green-100/30 dark:border-green-900/20' : paymentBehavior.color === 'amber' ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-100/30 dark:border-amber-900/20' : 'bg-red-50 dark:bg-red-900/10 border-red-100/30 dark:border-red-900/20'">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <component :is="paymentBehavior.icon" class="w-5 h-5" :class="paymentBehavior.color === 'green' ? 'text-green-500' : paymentBehavior.color === 'amber' ? 'text-amber-500' : 'text-red-500'" />
                <div>
                  <p class="text-[10px] font-black uppercase tracking-widest" :class="paymentBehavior.color === 'green' ? 'text-green-500' : paymentBehavior.color === 'amber' ? 'text-amber-500' : 'text-red-500'">{{ translations.payment_behavior || 'Comportement de paiement' }}</p>
                  <p class="text-sm font-black text-slate-900 dark:text-white mt-0.5">{{ paymentBehavior.label }}</p>
                </div>
              </div>
              <div class="text-right text-xs font-bold text-slate-400">
                <div v-if="client._payment_behavior.overdue_count > 0">{{ client._payment_behavior.overdue_count }} {{ translations.overdue_invoices || 'en retard' }}</div>
                <div v-if="client._payment_behavior.avg_delay_days !== null">~{{ client._payment_behavior.avg_delay_days }}j délai moyen</div>
              </div>
            </div>
          </div>

          <!-- Note Section -->
          <div v-if="client.note" class="bg-indigo-50/30 dark:bg-indigo-900/10 p-8 rounded-[2rem] border border-indigo-100/20">
              <h4 class="text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-4 flex items-center gap-2">
                  <StickyNote class="w-4 h-4" /> {{ translations.note }}
              </h4>
              <p class="text-slate-600 dark:text-slate-400 font-medium text-sm leading-relaxed italic">
                  "{{ client.note }}"
              </p>
          </div>
      </div>

      <!-- Footer -->
      <div class="flex flex-wrap items-center justify-between gap-3 mt-10 pt-6 border-t border-slate-100 dark:border-slate-800 sticky bottom-0 bg-white dark:bg-slate-900 z-10 py-2">
          <div class="flex items-center gap-2">
              <button v-if="client" @click="viewClientInvoices" class="flex items-center gap-2 px-5 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                  <FileText class="w-4 h-4" /> {{ translations.view_invoices || 'Voir ses factures' }}
              </button>
              <a v-if="client" :href="`/wp-json/my-easy-compta/v1/clients/${client.id}/statement?_wpnonce=${nonce}`" target="_blank"
                 class="flex items-center gap-2 px-5 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors"
                 :title="translations.client_statement || 'Relevé de compte PDF'"
              >
                  <Download class="w-4 h-4" /> {{ translations.statement || 'Relevé PDF' }}
              </a>
          </div>
          <button @click="closeModal" class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl">
              {{ translations.close || 'Fermer' }}
          </button>
      </div>

    </div>
  </dialog>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { X, Mail, Phone, Globe, MapPin, Building2, User, Hash, Flag, ShieldCheck, Banknote, StickyNote, Smartphone, TrendingUp, CheckCircle2, AlertCircle, FileText, Download, ThumbsUp, Clock, ThumbsDown } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const props = defineProps({
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    client: Object,
    currencyOptions: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(['close']);

const addon_siret_active = ref(false);
const localCurrencyOptions = ref(props.currencyOptions);

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const nonce = computed(() => window.myEasyComptaAdmin?.nonce || '');

const displayFields = computed(() => ({
    company_name: { label: translations.value.company_name, icon: Building2 },
    manager_name: { label: translations.value.manager_name, icon: User },
    email: { label: translations.value.email, icon: Mail },
    phone: { label: translations.value.phone, icon: Phone, type: "tel" },
    mobile_phone: { label: translations.value.mobile, icon: Smartphone, type: "tel" },
    website: { label: translations.value.website, icon: Globe, type: "url" },
    address: { label: translations.value.address, icon: MapPin },
    city: { label: translations.value.city, icon: MapPin },
    postal_code: { label: translations.value.postal_code, icon: Hash },
    country: { label: translations.value.country, icon: Flag },
}));

const router = useRouter();
const closeModal = () => emit('close');

const paymentBehavior = computed(() => {
    const pb = props.client?._payment_behavior;
    if (!pb || pb.total_invoices === 0) return { color: 'green', label: '-', icon: ThumbsUp };
    const overdueRatio = pb.overdue_count / pb.total_invoices;
    if (pb.overdue_count === 0 && (pb.avg_delay_days === null || pb.avg_delay_days <= 30)) {
        return { color: 'green', label: translations.value.good_payer || 'Bon payeur', icon: ThumbsUp };
    } else if (overdueRatio < 0.3 || pb.overdue_count <= 1) {
        return { color: 'amber', label: translations.value.occasional_delays || 'Retards occasionnels', icon: Clock };
    } else {
        return { color: 'red', label: translations.value.frequent_delays || 'Retards fréquents', icon: ThumbsDown };
    }
});

const viewClientInvoices = () => {
    closeModal();
    router.push({ name: 'Invoices', query: { client_id: props.client?.id } });
};

const formatAmount = (amount, symbol) => {
    if (!amount && amount !== 0) return '-';
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + (symbol || '€');
};

const getInitials = (name) => {
    if (!name) return '??';
    const parts = name.split(' ');
    if (parts.length > 1) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
};

const fetchOptions = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/options`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if (data.currency_options) {
            localCurrencyOptions.value = data.currency_options;
            addon_siret_active.value = data.addon_siret_active;
        }
    } catch (e) {}
};

const getCurrencyOption = (currencyId) => {
    const opts = localCurrencyOptions.value || props.currencyOptions;
    if (!opts || opts.length === 0) return "-";
    const option = opts.find(opt => opt.id === currencyId);
    return option ? `${option.name} (${option.symbol})` : "-";
};

onMounted(fetchOptions);
</script>
