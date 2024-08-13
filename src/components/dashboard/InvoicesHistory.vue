<template>
  <div>
    <div class="text-xl font-semibold">
      {{ translations.recently_paid_invoice }}
    </div>
    <div class="divider mt-2"></div>
    <div class="overflow-x-auto">
      <div v-if="loading">
        <div class="flex flex-col gap-4 w-full">
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-32 w-full"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-full"></div>
        </div>
      </div>
      <div v-else-if="recentPayments.length === 0">
        <div class="text-gray-500">{{ translations.no_data_for_moment }}</div>
      </div>
      <table v-else class="table table-xs ecwp-table-dash-invoice">
        <tbody>
          <tr v-for="payment in recentPayments" :key="payment.invoice_number">
            <td>{{ payment.invoice_number }}</td>
            <td>{{ payment.total_amount }}{{ payment.symbol }}</td>
            <td>
              <span class="badge badge-outline badge-success badge-sm">
                {{ payment.method_name }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: "InvoicesHistory",
  data() {
    return {
      recentPayments: [],
      loading: true,
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
        console.error(
          "Erreur lors de la récupération des paiements récents:",
          error
        );
        this.loading = false;
      }
    },
  },
};
</script>

