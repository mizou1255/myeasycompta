<template>
  <div 
    id="my-easy-compta-admin-root"
    :class="['ecwp-app min-h-screen font-sans transition-all duration-500 flex relative overflow-hidden', { 'dark': isDark }]"
    class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-50"
  >
    <!-- Background Decor (Color Blobs) -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-purple-500/10 dark:bg-purple-600/5 blur-[120px] rounded-full animate-pulse"></div>
        <div class="absolute top-[20%] -right-[5%] w-[30%] h-[30%] bg-indigo-500/10 dark:bg-indigo-600/5 blur-[100px] rounded-full" style="animation: pulse 8s infinite"></div>
        <div class="absolute -bottom-[10%] left-[20%] w-[35%] h-[35%] bg-blue-500/10 dark:bg-blue-600/5 blur-[110px] rounded-full" style="animation: pulse 12s infinite"></div>
    </div>

    <!-- Sidebar -->
    <Sidebar 
      :isCollapsed="isCollapsed" 
      :isDark="isDark" 
      @toggle="toggleSidebar" 
      @toggle-theme="toggleTheme" 
    />
    
    <!-- Content Wrapper -->
    <div 
      class="transition-all duration-500 ease-in-out flex flex-col min-h-screen flex-1 min-w-0 relative z-10"
    >
        <!-- TopHeader -->
        <header class="h-24 px-8 flex items-center justify-between sticky top-0 z-30 bg-slate-50/60 dark:bg-slate-950/60 backdrop-blur-xl border-b border-white/20 dark:border-white/5">
            <div>
              <slot name="header">
                 <h1 class="text-3xl font-black tracking-tight flex items-center gap-2 dark:text-white">
                    {{ title }}
                 </h1>
                 <p v-if="subtitle" class="text-slate-500 font-medium mt-1">{{ subtitle }}</p>
              </slot>
            </div>

            <div class="flex items-center gap-4">
               <!-- Slot for extra header actions -->
               <slot name="actions"></slot>

               <!-- Search Trigger -->
                <button 
                  @click="showSearch = true" 
                  class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-100 dark:border-slate-800 rounded-2xl px-4 py-3 flex items-center gap-3 text-slate-400 font-medium text-sm hover:border-purple-300 dark:hover:border-purple-800 hover:bg-white dark:hover:bg-slate-900 transition-all group shadow-sm"
                >
                    <Search class="w-4 h-4 group-hover:text-purple-600 group-hover:scale-110 transition-all" />
                    <span class="mr-12 hidden md:inline group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors">Rechercher...</span>
                    <span class="bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-lg text-xs font-bold text-slate-500 hidden md:inline group-hover:bg-purple-100 dark:group-hover:bg-purple-900/50 group-hover:text-purple-600 transition-colors">⌘K</span>
                </button>
            </div>
        </header>

        <!-- Main Content Area with Right Column -->
        <div class="flex min-w-0 overflow-hidden bg-slate-50 dark:bg-slate-950 transition-colors duration-500 h-[calc(100vh-6rem)]">
           <main class="flex-1 p-8 pt-0 relative min-w-0 overflow-y-auto custom-scrollbar">
                <slot />
           </main>

           <!-- Global Right Column (Marketing) — masqué pour les utilisateurs avec licence -->
           <aside v-if="!licenseValid" class="hidden 2xl:flex flex-col w-[320px] shrink-0 h-full overflow-hidden border-l border-slate-100 dark:border-slate-800 z-20 bg-slate-50 dark:bg-slate-950 transition-colors duration-500">
               <MarketingBanner />
           </aside>
        </div>
    </div>

    <!-- Collapsible Right Drawer for smaller screens — masqué pour les utilisateurs avec licence -->
    <template v-if="!licenseValid">
        <button
          @click="toggleRightPanel"
          class="2xl:hidden fixed right-6 bottom-6 w-14 h-14 bg-gradient-to-br from-indigo-600 to-purple-600 text-white rounded-full flex items-center justify-center shadow-2xl z-[100] hover:scale-110 active:scale-95 transition-all"
        >
            <Zap v-if="!showRightPanel" class="w-6 h-6 animate-pulse" />
            <X v-else class="w-6 h-6" />
        </button>

        <div
            v-if="showRightPanel"
            class="2xl:hidden fixed inset-0 z-[90] bg-slate-900/50 backdrop-blur-sm"
            @click="showRightPanel = false"
        ></div>

        <div
            :class="[
                '2xl:hidden fixed right-0 top-0 bottom-0 w-[320px] max-w-[90vw] bg-white dark:bg-slate-900 z-[100] transition-transform duration-500 shadow-2xl overflow-y-auto',
                showRightPanel ? 'translate-x-0' : 'translate-x-full'
            ]"
        >
            <div class="h-full overflow-y-auto">
                 <MarketingBanner />
            </div>
        </div>
    </template>

    <!-- Global Search Modal -->
    <GlobalSearch :is-open="showSearch" @close="showSearch = false" @open="showSearch = true" />

    <slot name="modals"></slot>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from './Sidebar.vue';
import MarketingBanner from '@/components/MarketingBanner.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { Search, Zap, X } from 'lucide-vue-next';
import { provide } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['open-search']);
const route = useRoute();

const licenseValid = computed(() => {
  // Dev override: localStorage.setItem('ecwp_preview_free','1') puis F5 pour simuler version gratuite
  if (localStorage.getItem('ecwp_preview_free') === '1') return false;
  return !!window.myEasyComptaAdmin?.licenseValid;
});

// State
const isCollapsed = ref(false);
const isDark = ref(false);
const showRightPanel = ref(false);
const showSearch = ref(false);

const toggleSidebar = (val) => {
  isCollapsed.value = val;
};

const toggleRightPanel = () => {
    showRightPanel.value = !showRightPanel.value;
};

// Auto collapse on detail pages
watch(() => route.name, (newName) => {
    if (newName === 'QuoteViewDetail' || newName === 'InvoiceViewDetail') {
        isCollapsed.value = true;
    }
}, { immediate: true });

// Provide context to children
provide('isDark', isDark);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('ecwp_theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('ecwp_theme', 'light');
    }
};

const handleKeydown = (e) => {
    const isK = e.key === 'k' || e.key === 'K' || e.code === 'KeyK';
    if ((e.metaKey || e.ctrlKey) && isK) {
        e.preventDefault();
        e.stopPropagation();
        showSearch.value = !showSearch.value;
    }
    
    if (e.key === 'Escape' && showSearch.value) {
        showSearch.value = false;
    }
};

onMounted(() => {
    // Init Theme
    const savedTheme = localStorage.getItem('ecwp_theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    }

    // Init Sidebar - Auto collapse on small screens
    const savedSidebar = localStorage.getItem('ecwp_sidebar_collapsed');
    if (savedSidebar === 'true') {
        isCollapsed.value = true;
    }
    
    // Check screen size
    if (window.innerWidth < 1280) {
       isCollapsed.value = true;
    }

    // Also check on mount for detail pages
    if (route.name === 'QuoteViewDetail' || route.name === 'InvoiceViewDetail') {
        isCollapsed.value = true;
    }

    window.addEventListener('keydown', handleKeydown, true);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown, true);
});
</script>

<style scoped>
/* Custom scrollbar for the right panel */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.3);
  border-radius: 20px;
}
</style>
