<template>
  <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
          <TrendingUp class="w-5 h-5" />
        </div>
        <div>
          <p class="text-xs font-black uppercase tracking-widest text-slate-400">Trésorerie</p>
          <p class="text-sm font-black text-slate-900 dark:text-white">Flux de trésorerie</p>
        </div>
      </div>
      <!-- Year selector -->
      <select
        v-model="selectedYear"
        @change="fetchCashflow"
        class="bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-3 py-2 text-xs font-black text-slate-600 dark:text-slate-300 focus:ring-2 focus:ring-purple-500/20 cursor-pointer"
      >
        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
      </select>
    </div>

    <!-- Legend -->
    <div class="flex items-center gap-4 mb-4">
      <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Encaissé
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span> Dépenses
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span> Net
      </span>
    </div>

    <!-- Chart -->
    <div class="relative h-56">
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center">
        <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <canvas v-show="!loading" ref="chartCanvas" class="w-full h-full"></canvas>
    </div>

    <!-- Summary row -->
    <div v-if="!loading" class="grid grid-cols-3 gap-3 mt-5 pt-5 border-t border-slate-100 dark:border-slate-800">
      <div class="text-center">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total encaissé</p>
        <p class="text-base font-black text-emerald-600">{{ formatTotal(totalEarnings) }}</p>
      </div>
      <div class="text-center">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total dépenses</p>
        <p class="text-base font-black text-rose-500">{{ formatTotal(totalExpenses) }}</p>
      </div>
      <div class="text-center">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Solde net</p>
        <p class="text-base font-black" :class="totalNet >= 0 ? 'text-indigo-600' : 'text-rose-600'">{{ formatTotal(totalNet) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { TrendingUp } from 'lucide-vue-next';
import {
  Chart, BarController, BarElement, LineController, LineElement, PointElement,
  CategoryScale, LinearScale, Tooltip, Legend
} from 'chart.js';

Chart.register(BarController, BarElement, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend);

const chartCanvas = ref(null);
const loading = ref(true);
const chartInstance = ref(null);
const selectedYear = ref(new Date().getFullYear());
const availableYears = ref([new Date().getFullYear()]);
const cashflowData = ref(null);

const totalEarnings = computed(() => cashflowData.value?.earnings?.reduce((a, b) => a + b, 0) ?? 0);
const totalExpenses = computed(() => cashflowData.value?.expenses?.reduce((a, b) => a + b, 0) ?? 0);
const totalNet      = computed(() => totalEarnings.value - totalExpenses.value);

const formatTotal = (v) => {
    return v.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
};

const renderChart = () => {
    if (!chartCanvas.value || !cashflowData.value) return;
    if (chartInstance.value) { chartInstance.value.destroy(); chartInstance.value = null; }

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    const textColor = isDark ? '#94a3b8' : '#94a3b8';

    chartInstance.value = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: cashflowData.value.months,
            datasets: [
                {
                    label: 'Encaissé',
                    data: cashflowData.value.earnings,
                    backgroundColor: 'rgba(34,197,94,0.7)',
                    borderRadius: 6,
                    order: 2,
                },
                {
                    label: 'Dépenses',
                    data: cashflowData.value.expenses,
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderRadius: 6,
                    order: 2,
                },
                {
                    label: 'Net',
                    type: 'line',
                    data: cashflowData.value.net,
                    borderColor: 'rgba(99,102,241,1)',
                    backgroundColor: 'rgba(99,102,241,0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(99,102,241,1)',
                    pointRadius: 3,
                    fill: false,
                    tension: 0.3,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} €`
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 10, weight: '700' } },
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor, font: { size: 10 },
                        callback: (v) => v.toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' €',
                    },
                },
            },
        },
    });
};

const fetchCashflow = async () => {
    loading.value = true;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/stats/cashflow?year=${selectedYear.value}`, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin?.nonce || '' }
        });
        if (!res.ok) throw new Error('Failed');
        const data = await res.json();
        cashflowData.value = data;
        if (data.available_years?.length) {
            availableYears.value = [...data.available_years].reverse();
        }
        await nextTick();
        renderChart();
    } catch(e) {
    } finally {
        loading.value = false;
    }
};

onMounted(() => fetchCashflow());
</script>
