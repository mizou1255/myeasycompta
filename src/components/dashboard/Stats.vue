<template>
  <!-- Skeleton Loading -->
  <div v-if="loading" class="col-span-1 md:col-span-2 lg:col-span-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div v-for="n in 4" :key="n" class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 h-40 border border-slate-100 dark:border-slate-800 animate-pulse"></div>
  </div>
  
  <div v-else class="col-span-1 md:col-span-2 lg:col-span-4 space-y-6">
  <!-- KPI row -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    <!-- Unpaid -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-amber-500/10 transition-all duration-300 group">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shrink-0">
          <AlertCircle class="w-5 h-5" />
        </div>
        <p class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ translations.unpaid || 'Impayés' }}</p>
      </div>
      <h3 class="font-black text-slate-900 dark:text-white tracking-tight" :class="unpaidAmountsByCurrency.length > 1 ? 'text-xl' : 'text-2xl'">
        <template v-if="unpaidAmountsByCurrency.length">
          <span v-for="(curr, i) in unpaidAmountsByCurrency" :key="curr.currencyId">
            <span v-if="i > 0" class="text-slate-300 dark:text-slate-600 mx-1.5">·</span>{{ curr.total_amount }}{{ curr.symbol }}
          </span>
        </template>
        <span v-else class="text-slate-400">0 €</span>
      </h3>
    </div>

    <!-- Expenses -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-rose-500/10 transition-all duration-300 group">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 text-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shrink-0">
          <Wallet class="w-5 h-5" />
        </div>
        <p class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ translations.expenses || 'Dépenses' }}</p>
      </div>
      <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ MonthExpenses }}€</h3>
      <p class="text-[10px] text-slate-400 font-bold mt-1">{{ translations.current_month || 'Ce mois-ci' }}</p>
    </div>

    <!-- Earnings Month -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 transition-all duration-300 group">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shrink-0">
          <TrendingUp class="w-5 h-5" />
        </div>
        <p class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ translations.earnings || 'Revenus' }}</p>
      </div>
      <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
        <span v-if="currentMonthEarnings">{{ currentMonthEarnings.total }}{{ currentMonthEarnings.default_currency_symbol }}</span>
        <span v-else class="text-slate-400">0 €</span>
      </h3>
      <div class="flex items-center gap-1.5 mt-1">
        <p class="text-[10px] text-slate-400 font-bold">{{ translations.current_month || 'Ce mois-ci' }}</p>
        <span v-if="monthComparison?.yoy_pct !== null && monthComparison?.yoy_pct !== undefined"
              class="text-[10px] font-black px-1.5 py-0.5 rounded-md"
              :class="monthComparison.yoy_pct >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300' : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-300'">
          {{ monthComparison.yoy_pct >= 0 ? '+' : '' }}{{ monthComparison.yoy_pct }}% vs N-1
        </span>
      </div>
    </div>

    <!-- Total Earnings -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 group">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shrink-0">
          <CreditCard class="w-5 h-5" />
        </div>
        <p class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ translations.all_Earnings || 'Total revenus' }}</p>
      </div>
      <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
        <span v-if="totalEarnings">{{ totalEarnings.total }}{{ totalEarnings.default_currency_symbol }}</span>
        <span v-else class="text-slate-400">0 €</span>
      </h3>
      <p class="text-[10px] text-slate-400 font-bold mt-1">Cumul toutes périodes</p>
    </div>

    <!-- Profit net du mois -->
    <div class="rounded-[2rem] p-6 border shadow-sm hover:shadow-xl transition-all duration-300 group"
         :class="profitNet >= 0
           ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-900/30 hover:shadow-emerald-500/10'
           : 'bg-rose-50 dark:bg-rose-900/10 border-rose-100 dark:border-rose-900/30 hover:shadow-rose-500/10'">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shrink-0"
             :class="profitNet >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400'">
          <TrendingUp v-if="profitNet >= 0" class="w-5 h-5" />
          <TrendingDown v-else class="w-5 h-5" />
        </div>
        <p class="text-xs font-black uppercase tracking-wider" :class="profitNet >= 0 ? 'text-emerald-600/70 dark:text-emerald-400/70' : 'text-rose-600/70 dark:text-rose-400/70'">{{ translations.net_profit || 'Profit net' }}</p>
      </div>
      <h3 class="text-2xl font-black tracking-tight" :class="profitNet >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
        {{ profitNetFormatted }}{{ currentMonthEarnings?.default_currency_symbol || '€' }}
      </h3>
      <p class="text-[10px] font-bold mt-1" :class="profitNet >= 0 ? 'text-emerald-500/60' : 'text-rose-500/60'">{{ translations.current_month || 'Ce mois-ci' }}</p>
    </div>
  </div><!-- end KPI row -->

  <!-- Bottom row: Overdue + Top 5 clients + Aging -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- F-7 — Factures en retard -->
    <div
      class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm cursor-pointer hover:shadow-xl hover:shadow-red-500/10 transition-all duration-300 group"
      @click="router.push({ name: 'Invoices', query: { status: 'unpaid' } })"
      title="Voir les factures en retard"
    >
      <div class="flex items-start justify-between mb-5">
        <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
          <Clock class="w-6 h-6" />
        </div>
        <span v-if="overdue.count > 0" class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg">
          {{ overdue.count }} {{ overdue.count > 1 ? 'factures' : 'facture' }}
        </span>
        <span v-else class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-lg">À jour</span>
      </div>
      <p class="text-slate-500 font-bold text-xs uppercase tracking-wider mb-2">{{ translations.overdue_invoices || 'En retard' }}</p>
      <div v-if="overdue.amounts.length" class="font-black text-red-600 dark:text-red-400 tracking-tight" :class="overdue.amounts.length > 1 ? 'text-xl' : 'text-2xl'">
        <span v-for="(a, i) in overdue.amounts" :key="a.symbol">
          <span v-if="i > 0" class="text-red-300 dark:text-red-800 mx-1.5">·</span>{{ a.amount.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} {{ a.symbol }}
        </span>
      </div>
      <p v-else class="text-2xl font-black text-slate-900 dark:text-white">0 €</p>
    </div>

    <!-- F-6 — Top 5 clients -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 flex items-center justify-center">
          <Users class="w-6 h-6" />
        </div>
        <p class="text-slate-500 font-bold text-xs uppercase tracking-wider">{{ translations.top_clients || 'Top 5 clients' }}</p>
      </div>
      <div v-if="topClients.length" class="space-y-3">
        <div v-for="(client, i) in topClients" :key="client.id" class="flex items-center gap-3">
          <span class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-black flex items-center justify-center flex-shrink-0">{{ i + 1 }}</span>
          <span class="flex-1 text-sm font-bold text-slate-700 dark:text-slate-300 truncate">{{ client.company_name }}</span>
          <span class="text-sm font-black text-slate-900 dark:text-white">{{ client.total_paid.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} {{ client.symbol }}</span>
        </div>
      </div>
      <p v-else class="text-slate-400 text-sm font-medium">Aucun paiement enregistré.</p>
    </div>

    <!-- S2.2 — Vieillissement des créances -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 flex items-center justify-center">
          <BarChart2 class="w-6 h-6" />
        </div>
        <p class="text-slate-500 font-bold text-xs uppercase tracking-wider">{{ translations.aging_receivables || 'Vieillissement créances' }}</p>
      </div>
      <div v-if="hasAging" class="space-y-2.5">
        <div v-for="bucket in agingBuckets" :key="bucket.key" class="flex items-center gap-3">
          <span class="w-16 text-[10px] font-black uppercase shrink-0" :class="bucket.color">{{ bucket.label }}</span>
          <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" :class="bucket.barColor" :style="{ width: bucket.pct + '%' }"></div>
          </div>
          <span class="text-xs font-black text-slate-700 dark:text-slate-300 w-20 text-right shrink-0">
            {{ bucket.amount.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }} €
          </span>
          <span class="text-[10px] font-bold text-slate-400 w-6 text-right shrink-0">{{ bucket.count }}</span>
        </div>
      </div>
      <p v-else class="text-slate-400 text-sm font-medium">Aucune créance en retard.</p>
    </div>

  </div><!-- end bottom row -->

  </div><!-- end outer wrapper -->
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { AlertCircle, Wallet, TrendingUp, TrendingDown, CreditCard, Clock, Users, BarChart2 } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const unpaidAmountsByCurrency = ref([]);
const MonthExpenses = ref(0);
const currentMonthEarnings = ref(null);
const totalEarnings = ref(null);
const topClients = ref([]);
const overdue = ref({ count: 0, amounts: [] });
const aging = ref({ '0_30': { count: 0, amount: 0 }, '31_60': { count: 0, amount: 0 }, '61_90': { count: 0, amount: 0 }, '90+': { count: 0, amount: 0 } });
const monthComparison = ref(null);

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const parseAmount = (val) => parseFloat(String(val || 0).replace(/\s/g, '').replace(',', '.')) || 0;

const profitNet = computed(() => {
    const earnings = parseAmount(currentMonthEarnings.value?.total);
    const expenses = parseAmount(MonthExpenses.value);
    return Math.round((earnings - expenses) * 100) / 100;
});

const hasAging = computed(() => Object.values(aging.value).some(b => b.count > 0));

const agingBuckets = computed(() => {
    const data = aging.value;
    const totalAmount = Object.values(data).reduce((s, b) => s + b.amount, 0);
    const defs = [
        { key: '0_30',  label: '0-30j',   color: 'text-amber-500',  barColor: 'bg-amber-400' },
        { key: '31_60', label: '31-60j',  color: 'text-orange-500', barColor: 'bg-orange-400' },
        { key: '61_90', label: '61-90j',  color: 'text-red-500',    barColor: 'bg-red-400' },
        { key: '90+',   label: '90j+',    color: 'text-red-700',    barColor: 'bg-red-700' },
    ];
    return defs.map(d => ({
        ...d,
        count:  data[d.key]?.count  ?? 0,
        amount: data[d.key]?.amount ?? 0,
        pct:    totalAmount > 0 ? Math.round((data[d.key]?.amount ?? 0) / totalAmount * 100) : 0,
    }));
});

const profitNetFormatted = computed(() => {
    const abs = Math.abs(profitNet.value);
    const sign = profitNet.value < 0 ? '-' : '';
    return sign + abs.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

const fetchStats = async () => {
    try {
        const response = await fetch("/wp-json/my-easy-compta/v1/stats", {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if(!response.ok) throw new Error('Failed');
        const data = await response.json();
        
        unpaidAmountsByCurrency.value = data.unpaid || [];
        MonthExpenses.value = data.expenses || 0;
        currentMonthEarnings.value = data.current_month_earnings;
        totalEarnings.value = data.total_earnings;
        topClients.value = data.top_clients || [];
        overdue.value = data.overdue || { count: 0, amounts: [] };
        if (data.aging) aging.value = data.aging;
        if (data.month_comparison) monthComparison.value = data.month_comparison;
    } catch(e) {
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
});
</script>
