<template>
  <div class="h-full relative w-full">
      <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-2xl">
           <div class="w-8 h-8 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <canvas ref="chartCanvas" class="w-full h-full"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const chartCanvas = ref(null);
const loading = ref(true);
const chartInstance = ref(null);
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const fetchData = async () => {
    try {
        const res = await fetch("/wp-json/my-easy-compta/v1/stats/monthly-payments-expenses", {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if(!res.ok) throw new Error('Failed');
        const data = await res.json();
        
        createChart(data.months, data.payments, data.expenses);
    } catch(e) {
    } finally {
        loading.value = false;
    }
};

const createChart = (labels, earningsData, expensesData) => {
    if(chartInstance.value) chartInstance.value.destroy();
    
    const ctx = chartCanvas.value.getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    
    // Gradients
    const earningsGradient = ctx.createLinearGradient(0, 0, 0, 400);
    earningsGradient.addColorStop(0, 'rgba(168, 85, 247, 0.8)'); // purple-500
    earningsGradient.addColorStop(1, 'rgba(168, 85, 247, 0.1)');

    const expensesGradient = ctx.createLinearGradient(0, 0, 0, 400);
    expensesGradient.addColorStop(0, 'rgba(251, 113, 133, 0.8)'); // rose-400
    expensesGradient.addColorStop(1, 'rgba(251, 113, 133, 0.1)');

    chartInstance.value = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: translations.value.earnings || 'Revenus',
                    data: earningsData,
                    backgroundColor: earningsGradient,
                    borderRadius: 8,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                    borderSkipped: false
                },
                {
                    label: translations.value.expenses || 'Dépenses',
                    data: expensesData,
                    backgroundColor: expensesGradient,
                    borderRadius: 8,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // We use custom header usually, or keep minimal
                },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#fff' : '#0f172a',
                    bodyColor: isDark ? '#94a3b8' : '#64748b',
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: true,
                    boxPadding: 4,
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: isDark ? '#1e293b' : '#f1f5f9',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        color: isDark ? '#94a3b8' : '#64748b',
                        font: { family: 'Inter', weight: '600' }
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: isDark ? '#94a3b8' : '#64748b',
                        font: { family: 'Inter', weight: '600' }
                    },
                    border: { display: false }
                }
            }
        }
    });
};

onMounted(() => {
    fetchData();
});
</script>
