<template>
  <MainLayout
    :title="translations.dashboard || 'Dashboard'"
    :subtitle="translations.dashboard_subtitle || 'Vue d\'ensemble de votre activité comptable'"
  >
    <div class="flex flex-col gap-8">

      <!-- Welcome Hero Banner -->
      <div class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-purple-600 via-violet-600 to-indigo-700 p-8 shadow-2xl shadow-purple-500/20">
        <!-- Decorative blobs -->
        <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-10 left-1/3 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute top-1/2 right-1/4 w-20 h-20 rounded-full bg-white/5 -translate-y-1/2 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
          <div>
            <p class="text-purple-200 text-[11px] font-black uppercase tracking-widest mb-2">{{ currentDate }}</p>
            <h2 class="text-2xl sm:text-3xl font-black text-white mb-1.5">
              {{ translations.welcome_back || 'Bon retour' }} 👋
            </h2>
            <p class="text-purple-100/80 text-sm font-medium">
              {{ translations.dashboard_subtitle || 'Vue d\'ensemble de votre activité comptable' }}
            </p>
          </div>
          <router-link
            to="/invoices/new"
            class="flex items-center gap-2 px-6 py-3 bg-white text-purple-700 rounded-2xl text-sm font-black whitespace-nowrap hover:bg-purple-50 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5 active:translate-y-0 shrink-0"
          >
            <Plus class="w-4 h-4" />
            {{ translations.new_invoice || 'Nouvelle facture' }}
          </router-link>
        </div>
      </div>

      <!-- Stats KPI Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <Stats />
      </div>

      <!-- Quick Actions -->
      <div>
        <h3 class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-4 px-1">
          {{ translations.quick_actions || 'Actions rapides' }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            v-for="action in quickActions"
            :key="action.to"
            :to="action.to"
            class="group flex flex-col items-center gap-3 p-5 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 hover:border-purple-200 dark:hover:border-purple-900/50 hover:shadow-lg hover:shadow-purple-500/8 transition-all duration-200 hover:-translate-y-0.5"
          >
            <div
              class="w-12 h-12 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-md"
              :class="action.bgClass"
            >
              <component :is="action.icon" class="w-5 h-5 text-white" />
            </div>
            <span class="text-xs font-black text-slate-600 dark:text-slate-300 text-center leading-tight group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
              {{ action.label }}
            </span>
          </router-link>
        </div>
      </div>

      <!-- BI Analytics section -->
      <AnalyticsBI />

      <!-- Chart + Recent Payments -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Bar Chart: 2/3 -->
        <div class="xl:col-span-2 bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">
              {{ translations.revenue_evolution || 'Évolution du CA' }}
            </h3>
            <span class="bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-xl px-4 py-2 text-[11px] font-black uppercase tracking-wider">
              {{ translations.this_year || 'Cette année' }}
            </span>
          </div>
          <div class="h-[320px] w-full">
            <BarChart />
          </div>
        </div>

        <!-- Recent Payments: 1/3 -->
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
          <InvoicesHistory />
        </div>

      </div>


    </div>
  </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Plus, FileText, ClipboardList, UserPlus, Receipt } from 'lucide-vue-next';
import MainLayout from '@/components/layout/MainLayout.vue';
import Stats from '@/components/dashboard/Stats.vue';
import BarChart from '@/components/dashboard/BarChart.vue';
import InvoicesHistory from '@/components/dashboard/InvoicesHistory.vue';
import AnalyticsBI from '@/components/dashboard/AnalyticsBI.vue';

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const currentDate = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
});

const quickActions = computed(() => [
  {
    to: '/invoices/new',
    label: translations.value.new_invoice || 'Nouvelle facture',
    icon: FileText,
    bgClass: 'bg-gradient-to-br from-indigo-500 to-indigo-700',
  },
  {
    to: '/quotes/new',
    label: translations.value.new_quote || 'Nouveau devis',
    icon: ClipboardList,
    bgClass: 'bg-gradient-to-br from-purple-500 to-purple-700',
  },
  {
    to: '/clients?add=1',
    label: translations.value.new_client || 'Nouveau client',
    icon: UserPlus,
    bgClass: 'bg-gradient-to-br from-emerald-500 to-emerald-700',
  },
  {
    to: '/expenses?add=1',
    label: translations.value.new_expense || 'Nouvelle dépense',
    icon: Receipt,
    bgClass: 'bg-gradient-to-br from-amber-500 to-amber-700',
  },
]);
</script>
