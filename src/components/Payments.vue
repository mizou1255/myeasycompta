<template>
  <MainLayout :title="translations.payments || 'Payments'" :subtitle="translations.payments_subtitle || 'Manage received payments'">
     <template #actions>
        <button 
             v-if="exportActive"
             @click="goToExport"
             class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-100 dark:border-slate-700 px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
           >
              <Download class="w-4 h-4" />
              {{ translations.export || 'Export' }}
        </button>
     </template>

    <div class="space-y-6">
      <!-- Filters Top Bar -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800">
         <!-- Row 1: text/select filters -->
         <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative group">
                <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                <input
                  v-model="filters.invoice_number"
                  @input="debouncedFetch"
                  type="text"
                  class="kloxy-input !pr-12"
                  :placeholder="translations.search_short || 'Rechercher...'"
                >
            </div>

            <select
               v-model="filters.client"
               @change="fetchPayments(1)"
               class="flex-1 min-w-[200px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_clients || 'Tous les clients' }}</option>
               <option v-for="client in clients" :key="client.id" :value="client.company_name">{{ client.company_name }}</option>
            </select>

            <select
               v-model="filters.payment_method"
               @change="fetchPayments(1)"
               class="flex-1 min-w-[200px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.payment_method || 'Mode de paiement' }}</option>
               <option v-for="method in paymentMethods" :key="method.id" :value="method.method_name">{{ method.method_name }}</option>
            </select>
         </div>
         <!-- Row 2: date range + reset -->
         <div class="flex items-center gap-4 mt-4">
            <DateRangePicker
              class="flex-1"
              v-model:from="filters.date_from"
              v-model:to="filters.date_to"
              @change="fetchPayments(1)"
            />
            <button @click="resetFilters" class="p-4 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-purple-600 rounded-2xl transition-colors shadow-sm" :title="translations.reset || 'Reset'">
                <RefreshCcw class="w-5 h-5" />
            </button>
         </div>
         <!-- Quick date filters -->
         <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 mr-1">{{ translations.period || 'Période :' }}</span>
            <button v-for="qf in quickFilters" :key="qf.value"
               @click="setQuickFilter(qf.value)"
               :class="activeQuickFilter === qf.value ? 'bg-purple-600 text-white shadow-md shadow-purple-500/30' : 'bg-slate-50 dark:bg-slate-800 text-slate-500 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20'"
               class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
            >{{ qf.label }}</button>
            <button v-if="activeQuickFilter" @click="clearQuickFilter"
               class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 transition-all"
            >✕</button>
         </div>
      </div>

      <!-- Main List -->
      <div class="space-y-6">
             <!-- Stats / Controls -->
            <div class="flex items-center justify-between px-4">
                <div class="text-sm font-bold text-slate-500 dark:text-slate-400">
                    <span v-if="!loading">{{ totalCount }} {{ translations.payments || 'payments' }}</span>
                    <span v-else class="animate-pulse">{{ translations.loading || 'Loading...' }}</span>
                </div>
                
                <select
                    v-model="perPage"
                    @change="fetchPayments(1)"
                    class="bg-transparent border-none text-slate-500 font-bold text-sm focus:ring-0 cursor-pointer"
                    >
                    <option :value="10">{{ translations.per_page_10 || '10 per page' }}</option>
                    <option :value="20">{{ translations.per_page_20 || '20 per page' }}</option>
                    <option :value="50">{{ translations.per_page_50 || '50 per page' }}</option>
                </select>
            </div>

            <div v-if="loading" class="space-y-4">
               <div v-for="i in 5" :key="i" class="h-24 bg-white dark:bg-slate-900 rounded-[2rem] animate-pulse"></div>
            </div>

            <div v-else-if="payments.length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Receipt class="w-8 h-8 text-slate-300" />
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ translations.no_payments_found || 'No payment found' }}</h3>
            </div>

            <table v-else class="w-full border-separate border-spacing-y-3">
                <thead>
                    <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-4">
                        <th class="px-8 pb-2 text-left">{{ translations.invoice_client_col || 'Invoice / Client' }}</th>
                        <th class="px-8 pb-2 text-left hidden md:table-cell">{{ translations.date || 'Date' }}</th>
                        <th class="px-8 pb-2 text-left hidden lg:table-cell">{{ translations.payment_method || 'Method' }}</th>
                        <th class="px-8 pb-2 text-right">{{ translations.total_amount || 'Total' }}</th>
                        <th class="px-8 pb-2 text-right">{{ translations.actions || 'Actions' }}</th>
                    </tr>
                </thead>
                <tfoot v-if="loading" class="hidden"></tfoot>
                <transition-group tag="tbody" name="list">
                     <tr 
                         v-for="(payment, index) in payments"
                         :key="payment.id"
                         class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none transition-all duration-300 group"
                         :class="{'ring-2 ring-purple-500 ring-offset-2 dark:ring-offset-slate-950': highlightId === payment.id}"
                         :id="highlightId === payment.id ? 'highlighted-row' : null"
                         :style="{ transitionDelay: `${index * 50}ms` }"
                     >
                        <td class="px-8 py-6 rounded-l-[2rem]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center font-black text-emerald-600 dark:text-emerald-400 text-lg">
                                    <Check class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 dark:text-white text-base">{{ payment.invoice_number }}</div>
                                    <div class="text-xs font-bold text-slate-400 mt-1">{{ payment.company_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6 hidden md:table-cell font-bold text-slate-500 dark:text-slate-400 text-sm">
                             {{ payment.payment_date }}
                        </td>
                        <td class="px-8 py-6 hidden lg:table-cell">
                             <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-black uppercase tracking-widest">
                                 {{ payment.payment_method }}
                             </span>
                        </td>
                        <td class="px-8 py-6 text-right font-black text-slate-900 dark:text-white">
                            {{ formatAmount(payment.amount) }}
                        </td>
                        <td class="px-8 py-6 rounded-r-[2rem] text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editPayment(payment.id)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(payment.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                     </tr>
                </transition-group>
            </table>

             <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex justify-center mt-8">
                <nav class="flex gap-2 bg-white dark:bg-slate-900 p-2 rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800">
                    <button 
                    @click="goToPage(currentPage - 1)" 
                    :disabled="currentPage === 1"
                    class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:hover:bg-transparent text-slate-500 transition-colors"
                    >
                        <ChevronLeft class="w-5 h-5" />
                    </button>
                    
                    <button 
                    v-for="page in paginationButtons" 
                    :key="page"
                    @click="goToPage(page)"
                    :class="page === currentPage 
                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' 
                        : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-sm transition-all"
                    :disabled="page === '...'"
                    >
                        {{ page }}
                    </button>

                    <button 
                    @click="goToPage(currentPage + 1)" 
                    :disabled="currentPage === totalPages"
                    class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:hover:bg-transparent text-slate-500 transition-colors"
                    >
                        <ChevronRight class="w-5 h-5" />
                    </button>
                </nav>
            </div>
        </div>
     </div>

     <!-- Modals -->
    <payment-edit-modal
      v-if="editPaymentModal"
      :loading="loadingModal"
      :show-modal="editPaymentModal"
      modal-id="modal_payment_edit"
      :modal-title="translations.edit_payment"
      :payment="selectedPaymentData"
      :methods="paymentMethods"
      @close="editPaymentModal = false"
      @paymentEdited="fetchPayments"
    />
    
    <remove-modal
      modal-id="modal_payment_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      @confirm="deletePayment(selectedPayment)"
      @cancel="showRemoveModal = false"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import MainLayout from '@/components/layout/MainLayout.vue';
import { Download, Search, RefreshCcw, Receipt, Check, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import DateRangePicker from "@/components/DateRangePicker.vue";
import PaymentEditModal from "@/components/payments/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import { fetchSettings } from "@/api/api";
import { generatePaginationButtons, formatAmount as formatAmountHelper } from "@/utils/helpers";

// State
const payments = ref([]);
const clients = ref([]);
const paymentMethods = ref([]);
const loading = ref(true);
const loadingModal = ref(false);
const totalCount = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(10);
const paginationButtons = ref([]);
const filters = reactive({
    invoice_number: "",
    client: "",
    payment_date: "",
    total_amount: "",
    payment_method: "",
    date_from: "",
    date_to: "",
});
const selectedPayment = ref(null);
const selectedPaymentData = ref(null);
const editPaymentModal = ref(false);
const showRemoveModal = ref(false);
const highlightId = ref(null);
const settings = ref({});
const defaultCurrency = ref("€");

// Computed
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const activeExport = computed(() => settings.value.easy_compta_export_addon_active == 1);
const exportActive = computed(() => settings.value.easy_compta_export_addon_active == 1);
const activeQuickFilter = ref('');
const quickFilters = computed(() => [
    { value: 'week',    label: translations.value.this_week    || 'Cette semaine' },
    { value: 'month',   label: translations.value.this_month   || 'Ce mois' },
    { value: 'quarter', label: translations.value.this_quarter || 'Ce trimestre' },
    { value: 'year',    label: translations.value.this_year    || 'Cette année' },
]);

// Logic
const debouncedFetch = (() => {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(() => fetchPayments(1), 500);
    }
})();

const setQuickFilter = (period) => {
    const now = new Date();
    let from, to;
    if (period === 'week') {
        const day = now.getDay();
        const diffToMon = day === 0 ? -6 : 1 - day;
        from = new Date(now); from.setDate(now.getDate() + diffToMon);
        to = new Date(from); to.setDate(from.getDate() + 6);
    } else if (period === 'month') {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
        to = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else if (period === 'quarter') {
        const q = Math.floor(now.getMonth() / 3);
        from = new Date(now.getFullYear(), q * 3, 1);
        to = new Date(now.getFullYear(), q * 3 + 3, 0);
    } else if (period === 'year') {
        from = new Date(now.getFullYear(), 0, 1);
        to = new Date(now.getFullYear(), 11, 31);
    }
    const fmt = d => d.toISOString().slice(0, 10);
    filters.date_from = fmt(from);
    filters.date_to = fmt(to);
    activeQuickFilter.value = period;
    fetchPayments(1);
};

const clearQuickFilter = () => {
    filters.date_from = '';
    filters.date_to = '';
    activeQuickFilter.value = '';
    fetchPayments(1);
};

const resetFilters = () => {
    Object.keys(filters).forEach(key => filters[key] = "");
    activeQuickFilter.value = '';
    fetchPayments(1);
};

const fetchPayments = async (page = null) => {
    if(page) currentPage.value = page;
    loading.value = true;
    
    const query = new URLSearchParams({
        page: currentPage.value,
        per_page: perPage.value,
        ...filters
    });

    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/payments?${query.toString()}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        payments.value = data.payments || [];
        totalCount.value = data.total_count || 0;
        totalPages.value = data.total_pages || 1;
        paginationButtons.value = generatePaginationButtons(currentPage.value, totalPages.value);
    } catch (e) {} finally { loading.value = false; }
};

const fetchClients = async () => {
     try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/clients`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        clients.value = data.clients || [];
    } catch (e) {}
};

const fetchPaymentMethods = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/payments/methods`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        paymentMethods.value = data || [];
    } catch (e) {}
};

const loadSettings = async () => {
    try {
        const res = await fetchSettings();
        if(res.success) {
            settings.value = res.data;
            defaultCurrency.value = res.data.currency_symbol;
        }
    } catch (e) {}
};

const formatAmount = (amount) => {
    return formatAmountHelper(amount, defaultCurrency.value, settings.value.currency_position);
};

const goToPage = (page) => {
    if(page !== '...' && page >= 1 && page <= totalPages.value) fetchPayments(page);
};

// Actions
const confirmDelete = (id) => {
    selectedPayment.value = id;
    showRemoveModal.value = true;
};

const deletePayment = async (id) => {
    showRemoveModal.value = false;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/payments/${id}`, {
            method: "DELETE",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        fetchPayments();
    } catch (e) {}
};

const editPayment = async (id) => {
    loadingModal.value = true;
    editPaymentModal.value = true;
    try {
         const res = await fetch(`/wp-json/my-easy-compta/v1/payments/details/${id}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        selectedPaymentData.value = data;
    } catch (e) {} finally { loadingModal.value = false; }
};

const goToExport = () => {
    window.location.href = '/wp-admin/admin.php?page=my-easy-compta-export#tab4';
};

const findPaymentPage = async (id) => {
     try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/payments/find-page/${id}?per_page=${perPage.value}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if(data.page) fetchPayments(data.page);
        else fetchPayments(1);
    } catch(e) {
        fetchPayments(1);
    }
};

onMounted(() => {
    // Check highlight param
    const urlParams = new URLSearchParams(window.location.search);
    const highlight = urlParams.get('highlight');
    if(highlight) {
        highlightId.value = parseInt(highlight);
        findPaymentPage(highlightId.value);
        
        // Scroll effect
        nextTick(() => {
             setTimeout(() => {
                const el = document.getElementById('highlighted-row');
                if(el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => highlightId.value = null, 3000);
                }
             }, 1000);
        });
    } else {
        fetchPayments();
    }
    
    fetchClients();
    fetchPaymentMethods();
    loadSettings();
});

</script>
