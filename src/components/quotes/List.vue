<template>
  <MainLayout :title="translations.quotes || 'Quotes'" :subtitle="translations.quotes_subtitle || 'Manage all your client quotes'">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 right-8 z-[9999] animate-in fade-in slide-in-from-bottom-8 duration-300">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <template #actions>
      <router-link :to="{ name: 'QuoteNew' }" custom v-slot="{ navigate }">
        <button 
          @click="navigate"
          class="bg-purple-600 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 flex items-center gap-2"
        >
          <Plus class="w-4 h-4" />
          {{ translations.add || 'Ajouter' }}
        </button>
      </router-link>
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
                  v-model="filters.quote_number"
                  @input="debouncedFetch"
                  type="text"
                  class="kloxy-input !pr-12"
                  :placeholder="translations.filter || 'N° Devis...'"
                >
            </div>

            <select
               v-model="filters.client"
               @change="fetchQuotes(1)"
               class="flex-1 min-w-[200px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_clients || 'Tous les clients' }}</option>
               <option v-for="client in clients" :key="client.id" :value="client.company_name">{{ client.company_name }}</option>
            </select>

            <select
               v-model="filters.status"
               @change="fetchQuotes(1)"
               class="flex-1 min-w-[160px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_statuses || 'Tous les statuts' }}</option>
               <option value="draft">{{ translations.draft || 'Brouillon' }}</option>
               <option value="pending">{{ translations.pending || 'En attente' }}</option>
               <option value="approved">{{ translations.approved || 'Approuvé' }}</option>
               <option value="rejected">{{ translations.rejected || 'Refusé' }}</option>
            </select>

            <input
               v-model="filters.total_amount"
               @input="debouncedFetch"
               type="text"
               class="flex-1 min-w-[140px] kloxy-input"
               :placeholder="translations.amount || 'Montant'"
            >
         </div>
         <!-- Row 2: date range + reset -->
         <div class="flex items-center gap-4 mt-4">
            <DateRangePicker
              class="flex-1"
              v-model:from="filters.date_from"
              v-model:to="filters.date_to"
              @change="fetchQuotes(1)"
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
                <span v-if="!loading">{{ totalCount }} {{ translations.quotes || 'quotes' }}</span>
                <span v-else class="animate-pulse">{{ translations.loading || 'Loading...' }}</span>
            </div>
            
             <select
                  v-model="perPage"
                  @change="fetchQuotes(1)"
                  class="bg-transparent border-none text-slate-500 font-bold text-sm focus:ring-0 cursor-pointer"
                >
                  <option :value="10">{{ translations.per_page_10 || '10 per page' }}</option>
                  <option :value="20">{{ translations.per_page_20 || '20 per page' }}</option>
                  <option :value="50">{{ translations.per_page_50 || '50 per page' }}</option>
             </select>
        </div>

        <div v-if="loading" class="space-y-4">
           <!-- Skeletons -->
           <div v-for="i in 5" :key="i" class="h-24 bg-white dark:bg-slate-900 rounded-[2rem] animate-pulse"></div>
        </div>

        <div v-else-if="quotes.length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <FileText class="w-8 h-8 text-slate-300" />
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ translations.no_quotes_found || 'No quote found' }}</h3>
            <p class="text-slate-500 mt-2 font-medium">{{ translations.quote_help_text || 'Create your first quote to get started.' }}</p>
             <router-link :to="{ name: 'QuoteNew' }" class="inline-block mt-6 text-purple-600 font-black text-sm uppercase tracking-widest hover:underline">
                {{ translations.new_quote || 'Create a quote' }}
             </router-link>
        </div>

        <table v-else class="w-full border-separate border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-4">
                    <th class="px-4 pb-2 text-left w-8">
                        <button @click="toggleSelectAll" class="text-slate-400 hover:text-purple-600 transition-colors">
                            <CheckSquare v-if="selectedIds.size === quotes.length && quotes.length > 0" class="w-4 h-4 text-purple-600" />
                            <Square v-else class="w-4 h-4" />
                        </button>
                    </th>
                    <th class="px-4 pb-2 text-left w-[30%]">{{ translations.quote_client_col || 'Quote / Client' }}</th>
                    <th class="px-4 pb-2 text-left hidden lg:table-cell w-[20%]">{{ translations.status || 'Status' }}</th>
                    <th class="px-4 pb-2 text-right w-[20%]">{{ translations.total || 'Total' }}</th>
                    <th class="px-4 pb-2 text-right w-[30%]">{{ translations.actions || 'Actions' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="quote in quotes"
                    :key="quote.id"
                    :class="selectedIds.has(quote.id) ? 'bg-purple-50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-800' : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800'"
                    class="border shadow-sm hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none transition-all duration-300 group"
                >
                    <td class="pl-4 py-6 rounded-l-[2rem]">
                        <button @click="toggleSelectOne(quote.id)" class="text-slate-300 hover:text-purple-600 transition-colors">
                            <CheckSquare v-if="selectedIds.has(quote.id)" class="w-4 h-4 text-purple-600" />
                            <Square v-else class="w-4 h-4" />
                        </button>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/20 rounded-2xl flex items-center justify-center font-black text-purple-600 dark:text-purple-400 text-lg">
                                #
                            </div>
                            <div>
                                <div class="font-black text-slate-900 dark:text-white text-base">{{ quote.quote_number }}</div>
                                <div class="text-xs font-bold text-slate-400 mt-1">{{ quote.created }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6 hidden md:table-cell">
                         <div class="flex items-center gap-3">
                             <div class="w-8 h-8 bg-slate-100 dark:bg-slate-800 rounded-lg flex items-center justify-center font-bold text-xs text-slate-500 uppercase">
                                 {{ quote.client_name ? quote.client_name.charAt(0) : '?' }}
                             </div>
                             <span class="font-bold text-slate-600 dark:text-slate-400">{{ quote.client_name }}</span>
                         </div>
                    </td>
                    <td class="px-8 py-6 hidden lg:table-cell">
                         <div class="flex flex-col gap-1">
                           <span
                             class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5"
                             :class="{
                                'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400': quote.status === 'draft',
                                'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400': quote.status === 'pending',
                                'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400': quote.status === 'approved',
                                'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400': quote.status === 'rejected'
                             }"
                           >
                              <span class="w-1.5 h-1.5 rounded-full" :class="{
                                 'bg-slate-400': quote.status === 'draft',
                                 'bg-amber-500': quote.status === 'pending',
                                 'bg-green-500': quote.status === 'approved',
                                 'bg-red-500': quote.status === 'rejected'
                              }"></span>
                               {{
                                  quote.status == 'draft' ? (translations.draft || 'Brouillon') :
                                  quote.status == 'pending' ? (translations.pending || 'En attente') :
                                  quote.status == 'approved' ? (translations.approved || 'Payé/Approuvé') :
                                  (translations.rejected || 'Refusé')
                               }}
                           </span>
                           <span v-if="isExpired(quote)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5 bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
                               <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                               {{ translations.expired || 'Expiré' }}
                           </span>
                         </div>
                    </td>
                    <td class="px-4 py-6 text-right font-black text-slate-900 dark:text-white">
                        {{ formatAmount(quote.total_amount, quote.client_currency || defaultCurrency) }}
                    </td>
                    <td class="px-4 py-6 rounded-r-[2rem] text-right">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <router-link :to="{ name: 'QuoteViewDetail', params: { id: quote.id } }" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.view || 'View'">
                                <Eye class="w-4 h-4" />
                            </router-link>
                            <router-link :to="{ name: 'QuoteEdit', params: { id: quote.id } }" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.edit || 'Edit'">
                                <Pencil class="w-4 h-4" />
                            </router-link>

                            <button @click="confirmDuplicate(quote)" class="p-2 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.duplicate || 'Duplicate'">
                                <Copy class="w-4 h-4" />
                            </button>

                            <button @click="downloadPDF(quote)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.exportToPDF || 'Export PDF'">
                                <FileText class="w-4 h-4" />
                            </button>
                            
                            <button v-if="emailActive && quote.status === 'approved'" @click="openSendModal(quote)" class="p-2 text-slate-400 hover:text-green-500 hover:bg-green-50 dark:hover:bg-slate-800 rounded-xl transition-colors" title="Envoyer">
                                <Send class="w-4 h-4" />
                            </button>

                            <button v-if="quote.status === 'approved' && quote.converted != 1" @click="convertToInvoice(quote)" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors" title="Convertir en Facture">
                                <Zap class="w-4 h-4" />
                            </button>
                            
                            <!-- Dropdown -->
                            <div class="relative group/dropdown">
                                <button class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                                <div class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/50 py-2 z-20 invisible group-hover/dropdown:visible opacity-0 group-hover/dropdown:opacity-100 transition-all transform origin-top-right border border-slate-100 dark:border-slate-800 z-50">
                                    <!-- Sauvegarder comme modèle -->
                                    <button @click="saveAsTemplate(quote.id)" class="w-full text-left px-4 py-2.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 flex items-center gap-2">
                                        <BookmarkPlus class="w-3.5 h-3.5" /> {{ translations.save_as_template || 'Sauvegarder comme modèle' }}
                                    </button>
                                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                    <button @click="confirmDelete(quote)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                        <Trash2 class="w-3.5 h-3.5" /> {{ translations.delete || 'Supprimer' }}
                                    </button>
                                </div>
                            </div>
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

    <!-- Bulk action bar -->
    <Transition name="slide-up">
      <div v-if="selectedIds.size > 0" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-3.5 rounded-2xl shadow-2xl shadow-slate-900/40 border border-white/10 dark:border-slate-200">
        <span class="font-black text-sm mr-2">{{ selectedIds.size }} {{ translations.selected || 'sélectionné(s)' }}</span>
        <button @click="bulkAction('delete')" :disabled="bulkLoading" class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-black text-xs px-4 py-2 rounded-xl transition-colors disabled:opacity-50">
          <Trash2 class="w-3.5 h-3.5" />{{ translations.delete || 'Supprimer' }}
        </button>
        <button @click="clearSelection" class="p-2 rounded-xl hover:bg-white/10 dark:hover:bg-slate-100 transition-colors">
          <X class="w-4 h-4" />
        </button>
      </div>
    </Transition>

    <!-- Modals -->
    <remove-modal
      :show-modal="showRemoveModal"
      modal-id="modal_remove_quote"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      @confirm="deleteQuote(selectedQuote)"
      @cancel="showRemoveModal = false"
    />
    
    <confirm-modal
      :show-modal="showConfirmModal"
      :title="translations.are_you_sure"
      :message="translations.duplicate_quote_confirm"
      @confirm="duplicateQuote(selectedQuote)"
      @cancel="showConfirmModal = false"
    />

    <!-- Send Modal -->
    <SendQuoteModal 
       v-if="showSendModal"
       :show-modal="showSendModal"
       modal-id="modal_send_quote"
       :client="{ email: sendForm.client_email }"
       :quote-id="sendForm.quote_id"
       :subject="sendForm.email_subject"
       :content="sendForm.email_message"
       @close="showSendModal = false"
       @success="handleSendSuccess"
       @error="handleSendError"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router'; // Added for router.push
import MainLayout from '@/components/layout/MainLayout.vue';
import { Plus, Download, Search, RefreshCcw, Eye, Pencil, MoreVertical, Copy, FileText, Send, Trash2, ChevronLeft, ChevronRight, CheckCircle2, AlertCircle, X, Zap, CheckSquare, Square, BookmarkPlus } from 'lucide-vue-next';
import RemoveModal from "@/components/RemoveAlert.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
import SendQuoteModal from "@/components/quotes/SendModal.vue";
import DateRangePicker from "@/components/DateRangePicker.vue";
import { generatePaginationButtons } from "@/utils/helpers";
import { fetchSettings } from "@/api/api";

const router = useRouter(); // Initialized router

const quotes = ref([]);
const loading = ref(true);
const totalCount = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(10);
const paginationButtons = ref([]);
const toast = reactive({ visible: false, message: "", type: "success" });

const clients = ref([]);
const filters = reactive({
    quote_number: "",
    client: "",
    status: "",
    total_amount: "",
    date_from: "",
    date_to: "",
});

const showRemoveModal = ref(false);
const showConfirmModal = ref(false);
const selectedQuote = ref(null);
const selectedIds = ref(new Set());
const bulkLoading = ref(false);
const showSendModal = ref(false);
const sendForm = reactive({
    quote_id: null,
    client_email: "",
    email_subject: "",
    email_message: ""
});

const settings = ref({});
const defaultCurrency = ref("€");

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const exportActive = computed(() => settings.value.easy_compta_export_addon_active == 1);
const emailActive = computed(() => {
    return settings.value.easy_compta_email_addon_active == 1 || settings.value.easy_compta_email_addon_active == '1';
});

// Helpers
const showToast = (message, type = "success") => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const debouncedFetch = (() => {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(() => fetchQuotes(1), 500);
    }
})();

const activeQuickFilter = ref('');

const quickFilters = computed(() => [
    { value: 'week',    label: translations.value.this_week    || 'Cette semaine' },
    { value: 'month',   label: translations.value.this_month   || 'Ce mois' },
    { value: 'quarter', label: translations.value.this_quarter || 'Ce trimestre' },
    { value: 'year',    label: translations.value.this_year    || 'Cette année' },
]);

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
    fetchQuotes(1);
};

const clearQuickFilter = () => {
    filters.date_from = '';
    filters.date_to = '';
    activeQuickFilter.value = '';
    fetchQuotes(1);
};

const resetFilters = () => {
    Object.keys(filters).forEach(key => filters[key] = "");
    activeQuickFilter.value = '';
    fetchQuotes(1);
};

const fetchQuotes = async (page = null) => {
    if(page) currentPage.value = page;
    loading.value = true;
    
    const query = new URLSearchParams({
        page: currentPage.value,
        per_page: perPage.value,
        ...filters
    });
    
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes?${query.toString()}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        selectedIds.value = new Set();
        quotes.value = data.quotes || [];
        totalCount.value = data.total_count || 0;
        totalPages.value = data.total_pages || 1;
        paginationButtons.value = generatePaginationButtons(currentPage.value, totalPages.value);
    } catch(e) {
        showToast("Erreur lors du chargement", "error");
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if(page === '...' || page < 1 || page > totalPages.value) return;
    fetchQuotes(page);
};

const formatAmount = (amount, currency) => {
    const symbol = currency || defaultCurrency.value;
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' })
        .formatToParts(amount)
        .map(part => part.type === 'currency' ? symbol : part.value)
        .join('');
};

const isExpired = (quote) => {
    if (quote.status === 'approved' || quote.status === 'rejected') return false;
    if (!quote.due_date_raw) return false;
    return new Date(quote.due_date_raw) < new Date(new Date().toDateString());
};

// Actions
const confirmDelete = (quote) => {
    selectedQuote.value = quote;
    showRemoveModal.value = true;
};

const deleteQuote = async (quote) => {
    showRemoveModal.value = false;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/quotes/${quote.id}`, {
            method: "DELETE",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        showToast("Devis supprimé", "success");
        fetchQuotes();
    } catch (e) {}
};

const confirmDuplicate = (quote) => {
    selectedQuote.value = quote;
    showConfirmModal.value = true;
};

const duplicateQuote = async (quote) => {
     showConfirmModal.value = false;
     try {
        await fetch(`/wp-json/my-easy-compta/v1/quotes/${quote.id}/duplicate`, {
            method: "POST",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        showToast("Devis dupliqué", "success");
        fetchQuotes();
     } catch (e) {}
};

const downloadPDF = (quote) => {
     window.open(
        `/wp-json/my-easy-compta/v1/quotes/pdf/${quote.id}?_wpnonce=${window.myEasyComptaAdmin.nonce}`,
        "_blank"
      );
};

const saveAsTemplate = async (id) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/${id}/save-as-template`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ is_template: 1 }),
        });
        const data = await res.json();
        if (!res.ok || data.code) {
            showToast(data.message || "Erreur lors de la sauvegarde du modèle", "error");
        } else {
            showToast(translations.value.template_saved || "Modèle sauvegardé", "success");
            fetchQuotes();
        }
    } catch (e) {}
};

const goToExport = () => {
    window.location.href = '/wp-admin/admin.php?page=my-easy-compta-export#tab2';
};

const openSendModal = async (quote) => {
    sendForm.quote_id = quote.id;
    sendForm.email_subject = `${translations.value.quote || 'Devis'} #${quote.quote_number}`;
    sendForm.email_message = `${translations.value.hello || 'Bonjour'},\n\n${translations.value.please_find_attached_quote || 'Veuillez trouver ci-joint'} #${quote.quote_number}.\n\n${translations.value.cordially || 'Cordialement'},`;
    
    // Fetch client email
    try {
         const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/${quote.id}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        const clientDetail = data?.client || {};
        sendForm.client_email = clientDetail.email || "";
        const clientName = clientDetail.company_name || "";
        const replacements = {
            '{nom_client}': clientName,
            '{numero_document}': quote.quote_number || "",
            '{montant_total}': (quote.total_amount || "0.00") + " " + (settings.value.currency || "€"),
        };
        const applyReplacements = (str) => Object.keys(replacements).reduce((s, k) => s.split(k).join(replacements[k]), str || "");
        const defaultSubject = `${translations.value.quote || 'Devis'} #${quote.quote_number}`;
        const defaultContent = `${translations.value.hello || 'Bonjour'},\n\n${translations.value.please_find_attached_quote || 'Veuillez trouver ci-joint'} #${quote.quote_number}.\n\n${translations.value.cordially || 'Cordialement'},`;
        sendForm.email_subject = applyReplacements(settings.value.quote_email_subject || defaultSubject);
        sendForm.email_message = applyReplacements(settings.value.quote_email_content || defaultContent);
        showSendModal.value = true;
    } catch (e) {}
};

const handleSendSuccess = (msg) => {
    showToast(msg, "success");
};

const handleSendError = (msg) => {
    showToast(msg, "error");
};

const convertToInvoice = async (quote) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/convert-quote/${quote.id}`, {
            method: "POST",
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if (data.success) {
            showToast("Devis converti en facture avec succès", "success");
            fetchQuotes();
            // Redirect to invoice list after a delay or just stay here
            setTimeout(() => {
                router.push({ name: 'Invoices' });
            }, 1500);
        } else {
            showToast(data.message || "Erreur lors de la conversion", "error");
        }
    } catch (e) {
        showToast("Erreur lors de la conversion", "error");
    }
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

const fetchInvoicingSettings = async () => {
    try {
        const res = await fetchSettings();
        if(res && res.settings) {
            settings.value = res.settings;
            if(res.currencySymbol) defaultCurrency.value = res.currencySymbol;
        }
    } catch (e) {}
};

// ── Bulk selection ────────────────────────────────────────────────────
const toggleSelectAll = () => {
    if (selectedIds.value.size === quotes.value.length && quotes.value.length > 0) {
        selectedIds.value = new Set();
    } else {
        selectedIds.value = new Set(quotes.value.map(q => q.id));
    }
};

const toggleSelectOne = (id) => {
    const s = new Set(selectedIds.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selectedIds.value = s;
};

const clearSelection = () => { selectedIds.value = new Set(); };

const bulkAction = async (action) => {
    if (selectedIds.value.size === 0 || bulkLoading.value) return;
    bulkLoading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/quotes/bulk', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ action, ids: Array.from(selectedIds.value) }),
        });
        const data = await res.json();
        showToast(data.message || 'Supprimé', data.success ? 'success' : 'error');
        clearSelection();
        fetchQuotes();
    } catch(e) {
        showToast('Erreur lors de l\'action groupée', 'error');
    } finally {
        bulkLoading.value = false;
    }
};

onMounted(() => {
    fetchQuotes();
    fetchClients();
    fetchInvoicingSettings();
});
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.25s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateX(-50%) translateY(20px); }
</style>
