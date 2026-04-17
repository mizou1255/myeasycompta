<template>
  <aside
    :class="[
      isCollapsed ? 'w-24' : 'w-80',
      'h-[calc(100vh-32px)] sticky bg-white/80 dark:bg-slate-900/80 backdrop-blur-2xl border-r border-slate-100 dark:border-slate-800 transition-all duration-500 ease-in-out flex flex-col z-40'
    ]"
  >
    <!-- Brand / Logo -->
    <div class="h-24 flex items-center px-8 relative">
      <div class="flex items-center gap-4 transition-all duration-500" :class="isCollapsed ? 'justify-center w-full' : ''">
        <div class="relative w-11 h-11 flex-shrink-0 group">
          <div class="absolute inset-0 bg-gradient-to-tr from-purple-600 to-indigo-500 rounded-xl rotate-6 group-hover:rotate-12 transition-transform duration-300 opacity-20"></div>
          <div class="absolute inset-0 bg-gradient-to-tr from-purple-600 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30 transform transition-transform duration-300 group-hover:scale-105 overflow-hidden">
            <div class="relative flex items-end gap-1 h-5 z-10">
              <div class="w-1.5 h-2 bg-white/60 rounded-sm"></div>
              <div class="w-1.5 h-4 bg-white rounded-sm"></div>
              <div class="w-1.5 h-3 bg-white/80 rounded-sm"></div>
              <div class="w-1.5 h-5 bg-white rounded-sm"></div>
            </div>
          </div>
        </div>
        <div v-if="!isCollapsed" class="flex flex-col animate-in fade-in slide-in-from-left-4 duration-500">
          <div class="flex items-center">
            <span class="font-black text-2xl tracking-tighter text-slate-900 dark:text-white leading-none">myEasy</span>
            <span class="font-black text-2xl tracking-tighter bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent leading-none">Compta</span>
          </div>
          <span class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500 mt-1">v2</span>
        </div>
      </div>
      <button
        @click="toggleSidebar"
        class="absolute -right-3 top-1/2 -translate-y-1/2 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 w-6 h-6 rounded-full flex items-center justify-center text-slate-400 hover:text-purple-600 shadow-sm transition-colors z-50"
      >
        <ChevronLeft v-if="!isCollapsed" class="w-3 h-3" />
        <ChevronRight v-else class="w-3 h-3" />
      </button>
    </div>

    <!-- Links -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto no-scrollbar space-y-1">

      <!-- Core menu -->
      <router-link v-for="item in coreMenu" :key="item.path" :to="item.path" custom v-slot="{ isActive, navigate }">
        <button
          @click="navigate"
          @mouseenter="isCollapsed ? showTooltip($event, item) : null"
          @mouseleave="isCollapsed ? hideTooltip() : null"
          :class="[
            isActive
              ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30'
              : 'text-slate-500 hover:bg-purple-50 dark:hover:bg-slate-800 dark:text-slate-400 hover:text-purple-600',
            isCollapsed ? 'justify-center' : '',
            'w-full flex items-center p-4 rounded-2xl transition-all duration-300 group relative'
          ]"
        >
          <component :is="item.icon" :class="[isActive ? 'text-white' : 'group-hover:text-purple-600 transition-colors duration-300 text-slate-400', 'w-6 h-6 flex-shrink-0']" />
          <span v-if="!isCollapsed" class="ml-4 font-bold text-sm tracking-tight whitespace-nowrap">{{ item.label }}</span>
        </button>
      </router-link>

      <!-- Addon separator -->
      <template v-if="addonMenu.length">
        <div :class="isCollapsed ? 'my-3 mx-2 border-t border-slate-100 dark:border-slate-800' : 'my-3 mx-2 flex items-center gap-2'">
          <div v-if="!isCollapsed" class="h-px flex-1 bg-slate-100 dark:bg-slate-800"></div>
          <span v-if="!isCollapsed" class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600 whitespace-nowrap px-1">Modules</span>
          <div v-if="!isCollapsed" class="h-px flex-1 bg-slate-100 dark:bg-slate-800"></div>
        </div>

        <router-link v-for="item in addonMenu" :key="item.slug" :to="item.path" custom v-slot="{ isActive, navigate }">
          <button
            @click="navigate"
            @mouseenter="isCollapsed ? showTooltip($event, item) : null"
            @mouseleave="isCollapsed ? hideTooltip() : null"
            :class="[
              isActive
                ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30'
                : 'text-slate-500 hover:bg-purple-50 dark:hover:bg-slate-800 dark:text-slate-400 hover:text-purple-600',
              isCollapsed ? 'justify-center' : '',
              'w-full flex items-center p-4 rounded-2xl transition-all duration-300 group relative'
            ]"
          >
            <component :is="item.icon" :class="[isActive ? 'text-white' : 'group-hover:text-purple-600 transition-colors duration-300 text-slate-400', 'w-6 h-6 flex-shrink-0']" />
            <span v-if="!isCollapsed" class="ml-4 font-bold text-sm tracking-tight whitespace-nowrap">{{ item.label }}</span>
          </button>
        </router-link>
      </template>

      <!-- Settings -->
      <router-link to="/settings" custom v-slot="{ isActive, navigate }">
        <button
          @click="navigate"
          @mouseenter="isCollapsed ? showTooltip($event, { label: 'Réglages', icon: Settings }) : null"
          @mouseleave="isCollapsed ? hideTooltip() : null"
          :class="[
            isActive
              ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30'
              : 'text-slate-500 hover:bg-purple-50 dark:hover:bg-slate-800 dark:text-slate-400 hover:text-purple-600',
            isCollapsed ? 'justify-center' : '',
            'w-full flex items-center p-4 rounded-2xl transition-all duration-300 group relative mt-2'
          ]"
        >
          <Settings class="w-6 h-6 flex-shrink-0" :class="isActive ? 'text-white' : 'group-hover:text-purple-600 transition-colors duration-300 text-slate-400'" />
          <span v-if="!isCollapsed" class="ml-4 font-bold text-sm tracking-tight whitespace-nowrap">Réglages</span>
        </button>
      </router-link>

    </nav>

    <!-- Footer -->
    <div class="p-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
      <button
        @click="$emit('toggle-theme')"
        @mouseenter="isCollapsed ? showTooltip($event, { label: isDark ? 'Mode Jour' : 'Mode Nuit', icon: isDark ? Sun : Moon }) : null"
        @mouseleave="isCollapsed ? hideTooltip() : null"
        :class="isCollapsed ? 'justify-center' : ''"
        class="w-full flex items-center p-4 rounded-2xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all group"
      >
        <Sun v-if="!isDark" class="w-6 h-6 flex-shrink-0 group-hover:text-amber-500 transition-colors" />
        <Moon v-else class="w-6 h-6 flex-shrink-0 group-hover:text-purple-400 transition-colors" />
        <span v-if="!isCollapsed" class="ml-4 font-bold text-sm">{{ isDark ? 'Mode Nuit' : 'Mode Jour' }}</span>
      </button>
    </div>
  </aside>

  <!-- Label extension — appears as if the icon button is expanding to the right -->
  <Teleport to="body">
    <Transition name="nav-tooltip">
      <div
        v-if="tooltip.visible"
        :style="tooltip.style"
        class="fixed z-[9998] pointer-events-none flex items-center whitespace-nowrap"
      >
        <span class="font-bold text-sm tracking-tight" :class="tooltip.isActive ? 'text-white' : 'text-purple-700 dark:text-purple-300'">
          {{ tooltip.label }}
        </span>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import {
  LayoutDashboard, Users, FileText, Receipt, CreditCard, Undo2, Wallet,
  Settings, ChevronLeft, ChevronRight, Sun, Moon,
  ScrollText, Clock, Truck, Calendar, BarChart2, RefreshCcw,
} from 'lucide-vue-next';

const props = defineProps({
  isCollapsed: { type: Boolean, default: false },
  isDark:      { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'toggle-theme']);

const coreMenu = [
  { path: '/',         label: 'Tableau de bord', icon: LayoutDashboard },
  { path: '/clients',  label: 'Clients',          icon: Users },
  { path: '/quotes',   label: 'Devis',            icon: FileText },
  { path: '/invoices', label: 'Factures',         icon: Receipt },
  { path: '/payments', label: 'Paiements',        icon: CreditCard },
  { path: '/credits',  label: 'Avoirs',           icon: Undo2 },
  { path: '/expenses', label: 'Dépenses',         icon: Wallet },
];

const addonMenu = computed(() => {
  const s = window.myEasyComptaAdmin?.addonsStatus || {};
  return [
    { slug: 'contracts',    path: '/contracts',    label: 'Contrats',          icon: ScrollText, active: !!window.myEasyComptaAdmin?.contractsAddonActive },
    { slug: 'timetracking', path: '/timetracking', label: 'Temps & Fact.',     icon: Clock,      active: !!window.myEasyComptaAdmin?.timetrackingAddonActive },
    { slug: 'delivery',     path: '/delivery',     label: 'Bons de livraison', icon: Truck,      active: !!window.myEasyComptaAdmin?.deliveryAddonActive },
    { slug: 'planning',     path: '/planning',     label: 'Planning',          icon: Calendar,   active: !!s.planning },
    { slug: 'stats',        path: '/stats',        label: 'Statistiques',      icon: BarChart2,  active: !!s.stats },
    { slug: 'recurring',    path: '/recurring',    label: 'Récurrentes',       icon: RefreshCcw, active: !!s.recurring },
  ].filter(item => item.active);
});

// ── Tooltip ──────────────────────────────────────────────────────────────────
const tooltip = reactive({
  visible:  false,
  label:    '',
  icon:     null,
  isActive: false,
  height:   0,
  style:    {},
});

function showTooltip(event, item) {
  const rect     = event.currentTarget.getBoundingClientRect();
  const isActive = event.currentTarget.classList.toString().includes('from-purple');
  const dark     = props.isDark;

  tooltip.visible  = true;
  tooltip.label    = item.label;
  tooltip.icon     = item.icon;
  tooltip.height   = rect.height;
  tooltip.isActive = isActive;
  // Overlap 24px: left part is transparent so the button's own gradient shows through
  // → no competing colors, no visible seam at the junction
  tooltip.style    = {
    top:          rect.top + 'px',
    left:         (rect.right - 24) + 'px',
    height:       rect.height + 'px',
    paddingLeft:  '34px',
    paddingRight: '20px',
    borderRadius: '0 16px 16px 0',
    background:   isActive
      ? 'linear-gradient(to right, transparent 0px, #4338ca 24px)'
      : dark
        ? 'linear-gradient(to right, transparent 0px, rgba(15,23,42,0.97) 24px)'
        : 'linear-gradient(to right, transparent 0px, #faf5ff 24px)',
    boxShadow:    isActive
      ? '6px 0 20px rgba(147,51,234,0.30)'
      : '4px 0 12px rgba(0,0,0,0.06)',
  };
}

function hideTooltip() {
  tooltip.visible = false;
}

// ── Sidebar toggle ────────────────────────────────────────────────────────────
const toggleSidebar = () => {
  hideTooltip();
  emit('toggle', !props.isCollapsed);
  localStorage.setItem('ecwp_sidebar_collapsed', (!props.isCollapsed).toString());
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Label extension — grows out from the icon's right edge */
.nav-tooltip-enter-active { transition: opacity 0.18s ease, transform 0.18s cubic-bezier(0.34,1.56,0.64,1); }
.nav-tooltip-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.nav-tooltip-enter-from   { opacity: 0; transform: scaleX(0.4); transform-origin: left center; }
.nav-tooltip-leave-to     { opacity: 0; transform: scaleX(0.6); transform-origin: left center; }
</style>
