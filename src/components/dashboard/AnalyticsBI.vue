<template>
  <div class="space-y-6">

    <!-- Section header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-indigo-500/10 flex items-center justify-center">
          <BarChart2 class="w-4 h-4 text-indigo-500" />
        </div>
        <h3 class="text-base font-black text-slate-900 dark:text-white">Analytique avancée</h3>
      </div>
      <div class="flex items-center gap-2">
        <select v-model="year" @change="loadAll"
          class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 focus:outline-none">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button @click="loadAll" :disabled="loading" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-purple-600 transition-all disabled:opacity-50">
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- Summary KPIs -->
    <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div v-for="kpi in kpiCards" :key="kpi.label"
        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-4">
        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ kpi.label }}</div>
        <div class="text-xl font-black" :class="kpi.colorClass">{{ kpi.value }}</div>
      </div>
    </div>

    <!-- Cashflow chart + Forecast -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

      <!-- Cashflow: 12 months bar -->
      <div class="xl:col-span-2 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 p-6">
        <h4 class="text-sm font-black text-slate-900 dark:text-white mb-5">Trésorerie — 12 derniers mois</h4>
        <div v-if="cashflow.length" class="flex items-end gap-1.5 h-40">
          <div v-for="(m, i) in cashflow" :key="i"
            class="flex-1 flex flex-col items-center gap-1 group"
            :title="`${m.label}\nFacturé: ${fmt(m.invoiced)} €\nEncaissé: ${fmt(m.collected)} €`">
            <div class="w-full flex gap-0.5 items-end h-32">
              <!-- Invoiced bar -->
              <div class="flex-1 rounded-t-md bg-indigo-200 dark:bg-indigo-800 transition-all"
                :style="{ height: barPct(m.invoiced, maxVal) + '%' }"></div>
              <!-- Collected bar -->
              <div class="flex-1 rounded-t-md bg-emerald-400 dark:bg-emerald-600 transition-all"
                :style="{ height: barPct(m.collected, maxVal) + '%' }"></div>
            </div>
            <span class="text-[8px] text-slate-400 font-bold truncate w-full text-center">{{ m.label.slice(0,3) }}</span>
          </div>
        </div>
        <div v-else class="h-40 flex items-center justify-center text-slate-400 text-sm">Aucune donnée</div>
        <!-- Legend -->
        <div class="flex items-center gap-4 mt-3">
          <span class="flex items-center gap-1.5 text-[10px] text-slate-500"><span class="w-3 h-3 rounded-sm bg-indigo-200 dark:bg-indigo-800 inline-block"></span>Facturé</span>
          <span class="flex items-center gap-1.5 text-[10px] text-slate-500"><span class="w-3 h-3 rounded-sm bg-emerald-400 dark:bg-emerald-600 inline-block"></span>Encaissé</span>
        </div>
      </div>

      <!-- Forecast: next 3 months -->
      <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 p-6">
        <h4 class="text-sm font-black text-slate-900 dark:text-white mb-1">Prévision 3 mois</h4>
        <p class="text-[10px] text-slate-400 mb-5">Basé sur la moyenne glissante</p>
        <div v-if="forecast.length" class="space-y-3">
          <div v-for="(f, i) in forecast" :key="i" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
            <div>
              <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ f.label }}</div>
              <div class="text-[10px] text-slate-400">Prévision</div>
            </div>
            <div class="text-base font-black text-indigo-600 dark:text-indigo-400">{{ fmt(f.forecast) }} €</div>
          </div>
        </div>
        <div class="mt-4 p-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-center">
          <div class="text-[10px] text-indigo-500 font-black uppercase tracking-wider">Moyenne mensuelle</div>
          <div class="text-lg font-black text-indigo-600 dark:text-indigo-400">{{ fmt(forecastAvg) }} €</div>
        </div>
      </div>

    </div>

    <!-- Payments heatmap by weekday -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 p-6">
      <h4 class="text-sm font-black text-slate-900 dark:text-white mb-5">Paiements par jour de la semaine (12 mois)</h4>
      <div v-if="heatmap.length" class="grid grid-cols-7 gap-2">
        <div v-for="d in heatmap" :key="d.day" class="flex flex-col items-center gap-2">
          <div class="w-full aspect-square rounded-xl flex items-center justify-center text-xs font-black transition-all"
            :style="{ background: heatmapBg(d.count), color: d.count > 0 ? '#fff' : '#94a3b8' }">
            {{ d.count || '—' }}
          </div>
          <span class="text-[10px] font-bold text-slate-400">{{ d.day }}</span>
          <span class="text-[9px] text-slate-400">{{ fmt(d.total) }}€</span>
        </div>
      </div>
      <div v-else class="text-slate-400 text-sm text-center py-6">Aucune donnée</div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { BarChart2, RefreshCw } from 'lucide-vue-next';

const year     = ref(new Date().getFullYear());
const years    = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const loading  = ref(false);
const cashflow = ref([]);
const forecast = ref([]);
const forecastAvg = ref(0);
const heatmap  = ref([]);
const summary  = ref(null);

const nonce = () => window.myEasyComptaAdmin?.nonce;
const api   = (path) => fetch(`/wp-json/my-easy-compta/v1${path}`, { headers: { 'X-WP-Nonce': nonce() } }).then(r => r.json());

const fmt = (v) => Number(v).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

const maxVal = computed(() => Math.max(1, ...cashflow.value.map(m => Math.max(m.invoiced, m.collected))));

const barPct = (val, max) => Math.max(4, Math.round((val / max) * 100));

const maxCount = computed(() => Math.max(1, ...heatmap.value.map(d => d.count)));
const heatmapBg = (count) => {
  if (!count) return 'rgba(148,163,184,0.08)';
  const pct = count / maxCount.value;
  const opacity = 0.2 + pct * 0.8;
  return `rgba(99,102,241,${opacity.toFixed(2)})`;
};

const kpiCards = computed(() => {
  if (!summary.value) return [];
  const s = summary.value;
  return [
    { label: 'CA facturé',      value: fmt(s.total_invoiced) + ' €',    colorClass: 'text-indigo-600 dark:text-indigo-400' },
    { label: 'Encaissé',        value: fmt(s.total_collected) + ' €',   colorClass: 'text-emerald-600 dark:text-emerald-400' },
    { label: 'Dépenses',        value: fmt(s.total_expenses) + ' €',    colorClass: 'text-rose-500 dark:text-rose-400' },
    { label: 'Marge brute',     value: fmt(s.gross_margin) + ' €',      colorClass: s.gross_margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' },
  ];
});

async function loadAll() {
  loading.value = true;
  const [cf, fc, hm, sm] = await Promise.all([
    api('/analytics/cashflow'),
    api('/analytics/forecast'),
    api('/analytics/payments-heatmap'),
    api(`/analytics/summary?year=${year.value}`),
  ]);
  cashflow.value    = Array.isArray(cf) ? cf : [];
  forecast.value    = fc.forecast || [];
  forecastAvg.value = fc.avg || 0;
  heatmap.value     = Array.isArray(hm) ? hm : [];
  summary.value     = sm;
  loading.value = false;
}

onMounted(loadAll);
</script>
