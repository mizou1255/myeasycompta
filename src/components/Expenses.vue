<template>
  <MainLayout :title="translations.expenses || 'Expenses'" :subtitle="translations.expenses_subtitle || 'Manage your expenses'">
    
    <template #actions>
      <button 
        @click="addNew"
        class="bg-purple-600 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 flex items-center gap-2"
      >
         <Plus class="w-4 h-4" />
         {{ translations.add || 'Ajouter' }}
      </button>
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
         <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative group">
                <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                <input 
                  v-model="filters.label"
                  @input="debouncedFetch"
                  type="text" 
                  class="kloxy-input !pr-12"
                  :placeholder="translations.search_short || 'Search...'"
                >
            </div>
            
            <select 
               v-model="filters.category" 
               @change="fetchExpenses(1)"
               class="flex-1 min-w-[200px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_categories || 'All categories' }}</option>
               <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
            </select>

            <select 
               v-model="filters.client" 
               @change="fetchExpenses(1)"
               class="flex-1 min-w-[200px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_clients || 'All clients' }}</option>
               <option v-for="client in clients" :key="client.id" :value="client.company_name">{{ client.company_name }}</option>
            </select>

            <DateRangePicker
              v-model:from="filters.date_from"
              v-model:to="filters.date_to"
              @change="fetchExpenses(1)"
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

      <!-- Category Breakdown Chart -->
      <div v-if="expenseStats.length > 0" class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800">
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <PieChart class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ translations.expenses_by_category || 'Par catégorie' }}</h3>
          </div>
          <span class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ expenseStatsTotal }} {{ settings.currency_symbol || '€' }}</span>
        </div>
        <div class="space-y-3">
          <div v-for="(item, i) in expenseStats" :key="i" class="flex items-center gap-4">
            <div class="w-28 text-xs font-bold text-slate-600 dark:text-slate-400 truncate flex-shrink-0">{{ item.category }}</div>
            <div class="flex-1 h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :style="{ width: item.percent + '%', backgroundColor: categoryColors[i % categoryColors.length] }"
              ></div>
            </div>
            <div class="text-xs font-black text-slate-700 dark:text-slate-300 w-12 text-right flex-shrink-0">{{ item.percent }}%</div>
            <div class="text-xs font-mono text-slate-500 w-20 text-right flex-shrink-0">{{ item.total.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }}</div>
          </div>
        </div>
      </div>

      <!-- Main List -->
      <div class="space-y-6">
             <!-- Stats / Controls -->
            <div class="flex items-center justify-between px-4">
                <div class="text-sm font-bold text-slate-500 dark:text-slate-400">
                    <span v-if="!loading">{{ totalCount }} {{ translations.expenses || 'expenses' }}</span>
                    <span v-else class="animate-pulse">{{ translations.loading || 'Loading...' }}</span>
                </div>
                
                <select
                    v-model="perPage"
                    @change="fetchExpenses(1)"
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

            <div v-else-if="expenses.length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Wallet class="w-8 h-8 text-slate-300" />
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ translations.no_expenses_found || 'No expense found' }}</h3>
            </div>

            <table v-else class="w-full border-separate border-spacing-y-3">
                 <thead>
                    <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-4">
                        <th class="px-8 pb-2 text-left">{{ translations.client || 'Client' }}</th>
                        <th class="px-8 pb-2 text-left hidden md:table-cell">{{ translations.category || 'Category' }}</th>
                        <th class="px-8 pb-2 text-left hidden lg:table-cell text-right">{{ translations.date || 'Date' }}</th>
                        <th class="px-8 pb-2 text-right">{{ translations.total_amount || 'Total' }}</th>
                        <th class="px-8 pb-2 text-right">{{ translations.actions || 'Actions' }}</th>
                    </tr>
                </thead>
                <transition-group tag="tbody" name="list">
                     <tr 
                         v-for="(expense, index) in expenses" 
                         :key="expense.id"
                         class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none transition-all duration-300 group"
                         :style="{ transitionDelay: `${index * 50}ms` }"
                     >
                        <td class="px-8 py-6 rounded-l-[2rem]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center font-black text-rose-600 dark:text-rose-400 text-lg">
                                    <ShoppingBag class="w-5 h-5" />
                                </div>
                                <div class="font-black text-slate-900 dark:text-white text-base">{{ expense.client_name }}</div>
                            </div>
                        </td>
                        <td class="px-8 py-6 hidden md:table-cell text-sm font-bold text-slate-500">
                             {{ expense.category_name }}
                        </td>
                        <td class="px-8 py-6 hidden lg:table-cell text-right text-sm font-bold text-slate-500">
                            {{ expense.expense_date }}
                        </td>
                        <td class="px-8 py-6 text-right font-black text-slate-900 dark:text-white">
                            {{ formatAmount(expense.total_amount) }}
                        </td>
                        <td class="px-8 py-6 rounded-r-[2rem] text-right">
                             <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editExpense(expense)" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.edit || 'Edit'">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="openRemoveModal(expense)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.delete || 'Delete'">
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
    <AddExpenseModal ref="addModalRef" @expenseAdded="fetchExpenses(1)" />
    <EditExpenseModal 
      ref="editModalRef" 
      modal-id="modal_edit_expense"
      modal-title="Modifier la dépense"
      :categories="categories"
      :clients="clients"
      @expenseUpdated="fetchExpenses(currentPage)" 
    />
    
    <remove-modal
      modal-id="modal_expense_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      @confirm="deleteExpense"
      @cancel="showRemoveModal = false"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import MainLayout from '@/components/layout/MainLayout.vue';
import { Plus, Search, RefreshCcw, ShoppingBag, Pencil, Trash2, Wallet, ChevronLeft, ChevronRight, Download, PieChart } from 'lucide-vue-next';
import DateRangePicker from "@/components/DateRangePicker.vue";
import AddExpenseModal from "@/components/expenses/Add.vue";
import EditExpenseModal from "@/components/expenses/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import { generatePaginationButtons, formatAmount as formatAmountHelper } from "@/utils/helpers";
import { fetchSettings } from "@/api/api";

const route = useRoute();

// State
const expenses = ref([]);
const categories = ref([]);
const clients = ref([]);
const loading = ref(true);
const totalCount = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(10);
const paginationButtons = ref([]);
const settings = ref({});
const showRemoveModal = ref(false);
const selectedExpense = ref(null);

const addModalRef = ref(null);
const editModalRef = ref(null);
const expenseStats = ref([]);
const expenseStatsTotal = ref(0);

const categoryColors = ['#8b5cf6','#06b6d4','#f59e0b','#10b981','#ef4444','#3b82f6','#ec4899','#84cc16','#f97316','#6366f1'];

const filters = reactive({
    label: "",
    category: "",
    client: "",
    date_from: "",
    date_to: "",
});

// Computed
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const exportActive = computed(() => settings.value.easy_compta_export_addon_active == 1);

// Logic
const debouncedFetch = (() => {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(() => fetchExpenses(1), 500);
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
    fetchExpenses(1);
};

const clearQuickFilter = () => {
    filters.date_from = '';
    filters.date_to = '';
    activeQuickFilter.value = '';
    fetchExpenses(1);
};

const resetFilters = () => {
    Object.keys(filters).forEach(key => filters[key] = "");
    activeQuickFilter.value = '';
    fetchExpenses(1);
};

const fetchExpenses = async (page = null) => {
    if(page) currentPage.value = page;
    loading.value = true;
    const query = new URLSearchParams({
        page: currentPage.value,
        per_page: perPage.value,
        ...filters
    });
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/expenses?${query.toString()}`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        expenses.value = data.expenses || [];
        totalCount.value = parseInt(data.total_count || 0);
        totalPages.value = Math.ceil(totalCount.value / (perPage.value || 10)) || 1;
        paginationButtons.value = generatePaginationButtons(currentPage.value, totalPages.value);
    } catch (e) {} finally {
        loading.value = false;
        fetchExpenseStats();
    }
};

const fetchExpenseStats = async () => {
    const query = new URLSearchParams();
    if (filters.date_from) query.set('date_from', filters.date_from);
    if (filters.date_to) query.set('date_to', filters.date_to);
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/expenses/stats?${query.toString()}`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        expenseStats.value = data.stats || [];
        expenseStatsTotal.value = data.total || 0;
    } catch (e) {}
};

const fetchCategories = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/expenses/categories`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        categories.value = await res.json();
    } catch (e) {}
};

const fetchClients = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/clients?per_page=999`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        const rawClients = data.clients || data || [];
        clients.value = rawClients.map(c => ({
            text: c.company_name,
            value: String(c.id)
        }));
    } catch (e) {}
};

const fetchInvoicingSettings = async () => {
     try {
         const res = await fetchSettings();
         if(res.success) settings.value = res.data;
     } catch (e) {}
};

const goToPage = (page) => {
    if(page === '...' || page < 1 || page > totalPages.value) return;
    fetchExpenses(page);
};

const addNew = () => {
    addModalRef.value?.openModal();
};

const formatAmount = (amount) => {
    return formatAmountHelper(amount, settings.value.currency_symbol || '€', settings.value.currency_position || 'after');
};

const editExpense = (expense) => {
    editModalRef.value?.openModal(expense);
};

const openRemoveModal = (expense) => {
    selectedExpense.value = expense.id;
    showRemoveModal.value = true;
};

const deleteExpense = async () => {
    if(!selectedExpense.value) return;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/expenses/${selectedExpense.value}`, {
            method: "DELETE",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        showRemoveModal.value = false;
        fetchExpenses(currentPage.value);
    } catch (e) {}
};

const goToExport = () => { window.location.href = '/wp-admin/admin.php?page=my-easy-compta-export#tab2'; };

onMounted(async () => {
    fetchExpenses();
    fetchCategories();
    fetchClients();
    fetchInvoicingSettings();
    fetchExpenseStats();

    // Ouvrir la modale d'ajout si on arrive depuis le dashboard
    if (route.query.add === '1') {
        await nextTick();
        addModalRef.value?.openModal();
    }
});
</script>
