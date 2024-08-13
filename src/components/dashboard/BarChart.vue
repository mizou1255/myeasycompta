<template>
  <div>
    <div class="text-xl font-semibold">
      {{ translations.annual_payments_overview }}
    </div>
    <div class="divider mt-2"></div>
    <div class="h-full w-full pb-6">
      <div v-if="loading">
        <div class="flex flex-col gap-4 w-full">
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-32 w-full"></div>
          <div class="skeleton h-4 w-full"></div>
          <div class="skeleton h-4 w-full"></div>
        </div>
      </div>
      <canvas v-else id="bar-chart"></canvas>
    </div>
  </div>
</template>

<script>
export default {
  name: "BarChart",
  data() {
    return {
      payments: [],
      expenses: [],
      months: [],
      loading: true,
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  mounted() {
    this.fetchMonthlyPaymentsExpenses();
  },
  methods: {
    async fetchMonthlyPaymentsExpenses() {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/stats/monthly-payments-expenses",
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
        this.payments = data.payments;
        this.expenses = data.expenses;
        this.months = data.months;
        this.loading = false;
        this.$nextTick(() => {
          this.loadChart();
        });
      } catch (error) {
        console.error(
          "Erreur lors de la récupération des paiements et des dépenses mensuels:",
          error
        );
        this.loading = false;
      }
    },
    loadChart() {
      if (typeof Chart !== "undefined") {
        this.createChart();
      } else {
        console.error("Chart.js is not loaded");
      }
    },
    createChart() {
      const translations = this.translations;
      const ctx = document.getElementById("bar-chart").getContext("2d");
      new Chart(ctx, {
        type: "bar",
        data: {
          labels: this.months,
          datasets: [
            {
              label: translations.earnings,
              backgroundColor: "#42A5F5",
              data: this.payments,
            },
            {
              label: translations.expenses,
              backgroundColor: "#FF00AA",
              data: this.expenses,
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            x: {
              beginAtZero: true,
            },
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    },
  },
};
</script>
