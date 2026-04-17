<template>
  <Transition name="fade">
    <div
      v-if="isOpenComputed"
      class="fixed inset-0 z-[9999] flex items-start justify-center pt-24 px-4 sm:px-6 md:pt-32"
      @click.self="closeSearch"
    >
      <!-- Backdrop Blur -->
      <div 
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
        @click="closeSearch"
      ></div>
      
      <!-- Modal Box -->
      <div 
        class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all animate-in zoom-in-95 duration-200"
        @click.stop
      >
        <!-- Search Input Header -->
        <div class="relative border-b border-slate-100 dark:border-slate-800 p-2">
          <Search class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            @keydown.enter="handleEnter"
            @keydown.escape="closeSearch"
            @keydown.arrow-down.prevent="navigateResults(1)"
            @keydown.arrow-up.prevent="navigateResults(-1)"
            type="text"
            :placeholder="translations.search_placeholder || 'Rechercher un devis, une facture, un client...'"
            class="w-full bg-transparent pl-14 pr-24 py-5 text-lg font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none"
            ref="searchInput"
            autofocus
          />
          <div class="absolute right-6 top-1/2 -translate-y-1/2 flex items-center gap-2">
            <span v-if="loading" class="animate-spin"><Loader2 class="w-4 h-4 text-purple-600" /></span>
            <span class="hidden sm:inline-block px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-black text-slate-500 tracking-tighter shadow-inner">ESC</span>
          </div>
        </div>

        <!-- Quick Filters Area -->
        <div class="px-4 py-3 bg-slate-50/50 dark:bg-slate-800/30 flex items-center gap-2 overflow-x-auto no-scrollbar border-b border-slate-100 dark:border-slate-800">
           <button
             v-for="filter in quickFilters"
             :key="filter.type"
             @click="applyQuickFilter(filter.type)"
             :class="[
               'px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all shrink-0',
               activeFilter === filter.type 
                 ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/20' 
                 : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-100 dark:border-slate-700 hover:border-purple-200'
             ]"
           >
             <component :is="filter.icon" class="w-3 h-3" />
             {{ filter.label }}
           </button>
        </div>

        <!-- Scrollable Results Area -->
        <div class="max-h-[60vh] overflow-y-auto p-2 min-h-[300px] custom-scrollbar">
          
          <!-- State: Empty / Idle -->
          <div v-if="!searchQuery && !activeFilter" class="py-12 text-center">
             <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-slate-100 dark:border-slate-700">
                <Keyboard class="w-8 h-8 text-slate-300" />
             </div>
             <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-2">{{ translations.quick_shortcuts || 'Quick shortcuts' }}</h4>
             <div class="flex flex-wrap justify-center gap-6 mt-6">
                <div class="flex flex-col items-center gap-2">
                   <div class="flex gap-1">
                      <kbd class="px-2 py-1 bg-slate-50 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700 rounded text-xs font-bold text-slate-600 dark:text-slate-400">⌘</kbd>
                      <kbd class="px-2 py-1 bg-slate-50 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700 rounded text-xs font-bold text-slate-600 dark:text-slate-400">K</kbd>
                   </div>
                   <span class="text-[10px] text-slate-400 font-bold uppercase">{{ translations.shortcut_open || 'Open' }}</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                   <kbd class="px-3 py-1 bg-slate-50 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700 rounded text-xs font-bold text-slate-600 dark:text-slate-400">ESC</kbd>
                   <span class="text-[10px] text-slate-400 font-bold uppercase">{{ translations.shortcut_close || 'Close' }}</span>
                </div>
                 <div class="flex flex-col items-center gap-2">
                   <kbd class="px-2 py-1 bg-slate-50 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700 rounded text-xs font-bold text-slate-600 dark:text-slate-400">↵</kbd>
                   <span class="text-[10px] text-slate-400 font-bold uppercase">{{ translations.shortcut_choose || 'Choose' }}</span>
                </div>
             </div>
          </div>

          <!-- State: Loading -->
          <div v-else-if="loading && results.length === 0" class="space-y-2 p-2">
             <div v-for="i in 5" :key="i" class="h-16 bg-slate-50 dark:bg-slate-800/50 rounded-2xl animate-pulse"></div>
          </div>

          <!-- State: No Results -->
          <div v-else-if="searchQuery && !loading && results.length === 0" class="py-12 text-center animate-in fade-in duration-500">
             <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-rose-100 dark:border-rose-900/30">
                <SearchX class="w-8 h-8 text-rose-500" />
             </div>
             <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ translations.no_results || 'No results' }}</h4>
             <p class="text-[11px] text-slate-500 font-medium mt-1">{{ translations.no_results_help || 'Check the spelling or try another filter.' }}</p>
          </div>

          <!-- State: Results List -->
          <div v-else class="space-y-1">
             <div 
               v-for="(result, index) in results" 
               :key="`${result.type}-${result.id}`"
               @click="navigateToResult(result)"
               @mouseenter="selectedIndex = index"
               :class="[
                 'group flex items-center gap-4 p-4 rounded-[1.25rem] cursor-pointer transition-all border border-transparent',
                 selectedIndex === index 
                   ? 'bg-purple-600 text-white shadow-xl shadow-purple-500/30 scale-[1.01]' 
                   : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-600 dark:text-slate-300'
               ]"
             >
                <!-- Icon -->
                <div :class="[
                  'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors',
                  selectedIndex === index 
                    ? 'bg-white/20 text-white' 
                    : getIconContainerClass(result.type)
                ]">
                   <component :is="getTypeIcon(result.type)" class="w-5 h-5 transition-transform group-hover:scale-110" />
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                   <div class="flex items-center gap-2 mb-0.5">
                      <span :class="[
                        'text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-md',
                        selectedIndex === index ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'
                      ]">
                        {{ result.typeLabel }}
                      </span>
                      <h4 class="text-sm font-black truncate" :class="selectedIndex === index ? 'text-white' : 'text-slate-900 dark:text-white'">
                        {{ result.title }}
                      </h4>
                   </div>
                   <p class="text-[11px] font-medium truncate opacity-70">
                      {{ result.description }}
                   </p>
                </div>

                <!-- Meta/Status -->
                <div class="flex flex-col items-end gap-1">
                   <div v-if="result.amount" class="text-sm font-black whitespace-nowrap">
                      {{ formatAmount(result.amount) }}
                   </div>
                   <div v-if="result.date" class="text-[10px] font-bold opacity-60">
                      {{ formatDate(result.date) }}
                   </div>
                </div>

                <!-- Arrow -->
                <ChevronRight class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1" />
             </div>
          </div>
        </div>

        <!-- Footer Help -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-[10px] font-black uppercase tracking-tighter text-slate-400">
           <div class="flex items-center gap-4">
              <span class="flex items-center gap-1"><ArrowDownUp class="w-3 h-3" /> {{ translations.shortcut_navigate || 'Navigate' }}</span>
              <span class="flex items-center gap-1"><CornerDownLeft class="w-3 h-3" /> {{ translations.shortcut_select || 'Select' }}</span>
           </div>
           <div v-if="results.length > 0">
              {{ results.length }} {{ translations.no_results ? '' : 'results' }}
           </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import {
  Search,
  Loader2,
  Keyboard,
  SearchX,
  FileText,
  FileCheck,
  Users,
  CreditCard,
  Receipt,
  Undo2,
  ChevronRight,
  ArrowDownUp,
  CornerDownLeft
} from 'lucide-vue-next';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: null
  }
});

const emit = defineEmits(['close', 'open']);
const router = useRouter();

// State
const internalIsOpen = ref(false);
const searchQuery = ref('');
const loading = ref(false);
const results = ref([]);
const activeFilter = ref(null);
const selectedIndex = ref(-1);
const searchInput = ref(null);
let searchTimeout = null;

const quickFilters = [
  { type: 'invoices', label: 'Factures', icon: FileCheck },
  { type: 'quotes', label: 'Devis', icon: FileText },
  { type: 'clients', label: 'Clients', icon: Users },
  { type: 'payments', label: 'Paiements', icon: CreditCard },
  { type: 'expenses', label: 'Dépenses', icon: Receipt },
  { type: 'credits', label: 'Avoirs', icon: Undo2 },
];

// Computed
const isOpenComputed = computed(() => {
  return props.isOpen !== null ? props.isOpen : internalIsOpen.value;
});

const translations = computed(() => {
  return window.myEasyComptaAdmin?.easyComptaTranslations || {};
});

// Watchers
watch(() => props.isOpen, (newVal) => {
  if (newVal !== null) {
    internalIsOpen.value = newVal;
    if (newVal) {
      focusInput();
    }
  }
});

watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    performSearch();
  }, 300);
});

// Methods
const focusInput = () => {
  nextTick(() => {
    if (searchInput.value) {
      searchInput.value.focus();
    }
  });
};

const openSearch = () => {
  if (props.isOpen === null) {
    internalIsOpen.value = true;
  } else {
    emit('open');
  }
  focusInput();
};

const closeSearch = () => {
  if (props.isOpen === null) {
    internalIsOpen.value = false;
  }
  emit('close');
  // Reset
  searchQuery.value = '';
  results.value = [];
  activeFilter.value = null;
  selectedIndex.value = -1;
};

const performSearch = async () => {
  if (!searchQuery.value && !activeFilter.value) {
    results.value = [];
    return;
  }

  loading.value = true;
  try {
    const response = await fetch(
      `/wp-json/my-easy-compta/v1/search?q=${encodeURIComponent(searchQuery.value)}&type=${activeFilter.value || 'all'}`,
      {
        headers: {
          'X-WP-Nonce': window.myEasyComptaAdmin?.nonce || ''
        }
      }
    );

    const data = await response.json();
    results.value = data.results || [];
    selectedIndex.value = results.value.length > 0 ? 0 : -1;
  } catch (error) {
    results.value = [];
  } finally {
    loading.value = false;
  }
};

const applyQuickFilter = (type) => {
  activeFilter.value = activeFilter.value === type ? null : type;
  performSearch();
};

const navigateResults = (direction) => {
  if (results.value.length === 0) return;
  
  selectedIndex.value += direction;
  
  if (selectedIndex.value < 0) {
    selectedIndex.value = results.value.length - 1;
  } else if (selectedIndex.value >= results.value.length) {
    selectedIndex.value = 0;
  }
};

const handleEnter = () => {
  if (results.value.length > 0 && selectedIndex.value >= 0) {
    navigateToResult(results.value[selectedIndex.value]);
  }
};

const navigateToResult = (result) => {
  closeSearch();
  
  const target = {
    'invoice': { name: 'InvoiceViewDetail', params: { id: result.id } },
    'quote': { name: 'QuoteViewDetail', params: { id: result.id } },
    'client': { name: 'ClientView', params: { id: result.id } },
    'payment': { name: 'Payments' },
    'expense': { name: 'Expenses' },
    'credit': { name: 'Credits' },
  };
  
  const routeLocation = target[result.type];
  if (routeLocation) {
    router.push(routeLocation);
  } else {
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

const formatAmount = (amount) => {
  if (amount === null || amount === undefined) return '';
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
};

const getTypeIcon = (type) => {
  const icons = {
    'invoice': FileCheck,
    'quote': FileText,
    'client': Users,
    'payment': CreditCard,
    'expense': Receipt,
    'credit': Undo2,
  };
  return icons[type] || FileText;
};

const getIconContainerClass = (type) => {
  const classes = {
    'invoice': 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
    'quote': 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
    'client': 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400',
    'payment': 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
    'expense': 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400',
    'credit': 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400',
  };
  return classes[type] || 'bg-slate-50 dark:bg-slate-800 text-slate-500';
};

const handleGlobalKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    if (isOpenComputed.value) closeSearch();
    else openSearch();
  }
};

// Lifecycle
onMounted(() => {
  // Listeners are handled by MainLayout to avoid conflicts with WordPress
});

onUnmounted(() => {
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.2);
  border-radius: 10px;
}
</style>
