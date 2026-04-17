<template>
  <MainLayout :title="translations.credits || 'Credits'" :subtitle="translations.credits_subtitle || 'Manage your client credits'">
     
    <div class="space-y-6">
      <!-- Filters Top Bar -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800">
         <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative group">
                <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                <input 
                  v-model="filters.credit_number"
                  @input="debouncedFetch"
                  type="text" 
                  class="kloxy-input !pr-12"
                  :placeholder="translations.credit_number_placeholder || 'Credit n°...'"
                >
            </div>
            
            <input 
                v-model="filters.client"
                @input="debouncedFetch"
                type="text"
                class="flex-1 min-w-[180px] kloxy-input"
                :placeholder="translations.client || 'Client'"
            >

            <input 
                v-model="filters.invoice_number"
                @input="debouncedFetch"
                type="text"
                class="flex-1 min-w-[180px] kloxy-input"
                :placeholder="translations.invoice_ref_col || 'Invoice Ref'"
            >

            <button @click="resetFilters" class="p-4 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-purple-600 rounded-2xl transition-colors shadow-sm" :title="translations.reset || 'Reset'">
                <RefreshCcw class="w-5 h-5" />
            </button>
         </div>
      </div>

      <!-- Main List -->
      <div class="space-y-6">
             <!-- Stats / Controls -->
            <div class="flex items-center justify-between px-4">
                <div class="text-sm font-bold text-slate-500 dark:text-slate-400">
                    <span v-if="!loading">{{ totalCount }} {{ translations.credits || 'credits' }}</span>
                    <span v-else class="animate-pulse">{{ translations.loading || 'Loading...' }}</span>
                </div>
                
                <select
                    v-model="perPage"
                    @change="fetchCredits(1)"
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

            <div v-else-if="credits.length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Receipt class="w-8 h-8 text-slate-300" />
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ translations.no_credits_found || 'No credit found' }}</h3>
            </div>

            <table v-else class="w-full border-separate border-spacing-y-3">
                <thead>
                    <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-4">
                        <th class="px-8 pb-2 text-left">{{ translations.credit_client_col || 'Credit / Client' }}</th>
                        <th class="px-8 pb-2 text-left hidden md:table-cell">{{ translations.invoice_ref_col || 'Invoice Ref' }}</th>
                        <th class="px-8 pb-2 text-left hidden lg:table-cell text-right">{{ translations.date || 'Date' }}</th>
                        <th class="px-8 pb-2 text-right">{{ translations.total_amount || 'Total' }}</th>
                        <th class="px-8 pb-2 text-right">{{ translations.actions || 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                     <tr 
                         v-for="credit in credits"
                         :key="credit.id"
                         class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none transition-all duration-300 group"
                         :class="{'ring-2 ring-purple-500 ring-offset-2 dark:ring-offset-slate-950': highlightId === credit.id}"
                         :id="highlightId === credit.id ? 'highlighted-row' : null"
                     >
                        <td class="px-8 py-6 rounded-l-[2rem]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center font-black text-rose-600 dark:text-rose-400 text-lg">
                                    <FileMinus class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 dark:text-white text-base">{{ credit.credit_number }}</div>
                                    <div class="text-xs font-bold text-slate-400 mt-1">{{ credit.client_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6 hidden md:table-cell">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-black uppercase tracking-widest">
                                {{ credit.invoice_number }}
                            </span>
                        </td>
                        <td class="px-8 py-6 hidden lg:table-cell text-right font-bold text-slate-500 dark:text-slate-400 text-sm">
                             {{ credit.issue_date || credit.created_at }}
                        </td>
                        <td class="px-8 py-6 text-right font-black text-slate-900 dark:text-white">
                            {{ formatAmount(credit.total_amount) }}
                        </td>
                        <td class="px-8 py-6 rounded-r-[2rem] text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="exportToPDF(credit.credit_id)" :disabled="loadingPdfId===credit.credit_id" class="p-2 text-slate-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                    <Loader2 v-if="loadingPdfId===credit.credit_id" class="w-4 h-4 animate-spin" />
                                    <FileText v-else class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(credit.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                     </tr>
                </tbody>
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

    <remove-modal
      modal-id="modal_credit_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      @confirm="deleteCredit(selectedCredit)"
      @cancel="showRemoveModal = false"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import MainLayout from '@/components/layout/MainLayout.vue';
import { Search, RefreshCcw, Receipt, FileMinus, FileText, Trash2, ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next';
import RemoveModal from "@/components/RemoveAlert.vue";
import { fetchSettings } from "@/api/api";
import { generatePaginationButtons, formatAmount as formatAmountHelper } from "@/utils/helpers";

// State
const credits = ref([]);
const loading = ref(true);
const loadingPdfId = ref(null);
const totalCount = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(10);
const paginationButtons = ref([]);
const filters = reactive({
    credit_number: "",
    invoice_number: "",
    client: "",
    created_at: "",
    total_amount: "",
});
const selectedCredit = ref(null);
const showRemoveModal = ref(false);
const highlightId = ref(null);
const settings = ref({});
const defaultCurrency = ref("€");

// Computed
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

// Logic
const debouncedFetch = (() => {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(() => fetchCredits(1), 500);
    }
})();

const resetFilters = () => {
    Object.keys(filters).forEach(key => filters[key] = "");
    fetchCredits(1);
};

const fetchCredits = async (page = null) => {
    if(page) currentPage.value = page;
    loading.value = true;
    
    const query = new URLSearchParams({
        page: currentPage.value,
        per_page: perPage.value,
        ...filters
    });

    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/credits?${query.toString()}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        credits.value = data.credits || [];
        totalCount.value = data.total_count || 0;
        totalPages.value = data.total_pages || 1;
        paginationButtons.value = generatePaginationButtons(currentPage.value, totalPages.value);
    } catch (e) {} finally { loading.value = false; }
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
    if(page !== '...' && page >= 1 && page <= totalPages.value) fetchCredits(page);
};

// Actions
const confirmDelete = (id) => {
    selectedCredit.value = id;
    showRemoveModal.value = true;
};

const deleteCredit = async (id) => {
    showRemoveModal.value = false;
    try {
         await fetch(`/wp-json/my-easy-compta/v1/credits/${id}`, {
            method: "DELETE",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
         });
         fetchCredits();
    } catch (e) {}
};

const exportToPDF = async (id) => {
    loadingPdfId.value = id;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/credits/pdf/${id}`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if(!res.ok) throw new Error('PDF failed');
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        window.open(url);
    } catch (e) {} finally { loadingPdfId.value = null; }
};

const findCreditPage = async (id) => {
     try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/credits/find-page/${id}?per_page=${perPage.value}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if(data.page) fetchCredits(data.page);
        else fetchCredits(1);
    } catch(e) { 
        fetchCredits(1);
    }
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const highlight = urlParams.get('highlight');
    if(highlight) {
        highlightId.value = parseInt(highlight);
        findCreditPage(highlightId.value);
        
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
        fetchCredits();
    }
    loadSettings();
});
</script>
