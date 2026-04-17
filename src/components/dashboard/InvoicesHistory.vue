<template>
  <div class="ecwp-card h-full">
    <div class="p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center ring-1 ring-primary-100 dark:ring-primary-800">
          <History class="w-5 h-5 text-primary-600 dark:text-primary-400" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">
            {{ translations.recently_paid_invoice }}
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ translations.recent_payments_title || 'Paiements récents' }}</p>
        </div>
      </div>

      <div class="overflow-x-auto max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
        <div v-if="loading">
          <div class="flex flex-col gap-3 w-full animate-pulse">
            <div v-for="i in 3" :key="i" class="h-20 bg-gray-100 dark:bg-gray-800 rounded-xl w-full"></div>
          </div>
        </div>
        <div v-else-if="recentPayments.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
          <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
            <Inbox class="w-6 h-6 text-gray-400 dark:text-gray-500" />
          </div>
          <div class="text-gray-500 dark:text-gray-400 font-medium">{{ translations.no_data_for_moment }}</div>
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="(payment, index) in recentPayments"
            :key="payment.invoice_number"
            class="flex items-center justify-between p-4 rounded-xl bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-800 border border-gray-100 dark:border-gray-700/50 transition-all duration-300 hover:shadow-md group"
            :style="{ animationDelay: `${index * 50}ms` }"
          >
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shrink-0">
              <FileText class="w-4 h-4 text-green-600 dark:text-green-400" />
            </div>
            <div class="flex-1 min-w-0 ml-3">
              <div class="flex items-center justify-between gap-2 mb-0.5">
                <span class="font-semibold text-gray-900 dark:text-white truncate text-sm">{{ payment.invoice_number }}</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 text-[10px] font-bold shrink-0 max-w-[120px]">
                  <CheckCircle class="w-3 h-3 shrink-0" />
                  <span class="truncate">{{ payment.method_name }}</span>
                </span>
              </div>
              <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ payment.total_amount }}{{ payment.symbol }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { History, Inbox, FileText, CheckCircle } from 'lucide-vue-next';

export default {
  name: "InvoicesHistory",
  components: { History, Inbox, FileText, CheckCircle },
  data() {
    return {
      recentPayments: [],
      loading: true,
      symbol: "€",
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  mounted() {
    this.fetchRecentPayments();
  },
  methods: {
    async fetchRecentPayments() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/stats/recent-payments",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        this.recentPayments = data;
        this.loading = false;
      } catch (error) {
        this.loading = false;
      }
    },
  },
};
</script>
