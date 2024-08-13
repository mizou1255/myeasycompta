<template>
  <div class="mt-8">
    <div class="grid lg:grid-cols-4 mt-2 md:grid-cols-2 grid-cols-1 gap-6">
      <div class="stats shadow border">
        <div class="stat">
          <div class="stat-figure dark:text-slate-300 text-primary">
            <i class="fas fa-hand-holding-usd text-4xl"></i>
          </div>
          <div class="stat-title dark:text-slate-300">
            {{ translations.unpaid }}
          </div>
          <div class="stat-value dark:text-slate-300 text-primary">
            <div v-if="loading">
              <div class="flex flex-col gap-4 w-40">
                <div class="flex gap-4 items-center">
                  <div class="flex flex-col gap-4">
                    <div class="skeleton h-4 w-40"></div>
                    <div class="skeleton h-4 w-40"></div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else>
              <div
                v-for="currency in unpaidAmountsByCurrency"
                :key="currency.currencyId"
              >
                {{ currency.total_amount }}{{ currency.symbol }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="stats shadow border">
        <div class="stat">
          <div class="stat-figure dark:text-slate-300 text-primary">
            <i class="fas fa-receipt text-4xl"></i>
          </div>
          <div class="stat-title dark:text-slate-300">
            {{ translations.expenses }}
          </div>
          <div class="stat-value dark:text-slate-300 text-primary">
            <div v-if="loading">
              <div class="flex flex-col gap-4 w-40">
                <div class="flex gap-4 items-center">
                  <div class="flex flex-col gap-4">
                    <div class="skeleton h-4 w-40"></div>
                    <div class="skeleton h-4 w-40"></div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else>
              <span v-if="MonthExpenses">
                {{ MonthExpenses }}{{ symbol }}
              </span>
              <span v-else> 0{{ symbol }} </span>
            </div>
          </div>
          <div class="stat-desc">{{ translations.current_month }}</div>
        </div>
      </div>
      <div class="stats shadow border">
        <div class="stat">
          <div class="stat-figure dark:text-slate-300 text-primary">
            <i class="far fa-money-bill-alt text-4xl"></i>
          </div>
          <div class="stat-title dark:text-slate-300">
            {{ translations.earnings }}
          </div>
          <div class="stat-value dark:text-slate-300 text-primary">
            <div v-if="loading">
              <div class="flex flex-col gap-4 w-40">
                <div class="flex gap-4 items-center">
                  <div class="flex flex-col gap-4">
                    <div class="skeleton h-4 w-40"></div>
                    <div class="skeleton h-4 w-40"></div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else>
              <span v-if="currentMonthEarnings">
                {{ currentMonthEarnings.total }}
                {{ currentMonthEarnings.default_currency_symbol }}
              </span>
              <span v-else> 0{{ symbol }} </span>
            </div>
          </div>
          <div class="stat-desc">{{ translations.current_month }}</div>
        </div>
      </div>
      <div class="stats shadow border">
        <div class="stat">
          <div class="stat-figure dark:text-slate-300 text-primary">
            <i class="fas fa-file-invoice-dollar text-4xl"></i>
          </div>
          <div class="stat-title dark:text-slate-300">
            {{ translations.all_Earnings }}
          </div>
          <div class="stat-value dark:text-slate-300 text-primary">
            <div v-if="loading">
              <div class="flex flex-col gap-4 w-40">
                <div class="flex gap-4 items-center">
                  <div class="flex flex-col gap-4">
                    <div class="skeleton h-4 w-40"></div>
                    <div class="skeleton h-4 w-40"></div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else>
              <span v-if="totalEarnings">
                {{ totalEarnings.total }}
                {{ totalEarnings.default_currency_symbol }}
              </span>
              <span v-else> 0{{ totalEarnings.default_currency_symbol }} </span>
            </div>
          </div>
          <div class="stat-desc"></div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
export default {
  name: "Stats",
  data() {
    return {
      unpaidAmountsByCurrency: [],
      MonthExpenses: 0,
      currentMonthEarnings: "",
      totalEarnings: "",
      symbol: "",
      loading: false,
    };
  },
  mounted() {
    this.calculateUnpaidAmount();
    this.calculateCurrentMonthExpenses();
    this.fetchCurrentMonthEarnings();
    this.fetchTotalEarnings();
  },
  methods: {
    async calculateUnpaidAmount() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/stats/unpaid-invoices",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );
        const unpaidInvoices = await response.json();
        const unpaidAmountsByCurrency = [];
        for (const currencyId in unpaidInvoices) {
          const { total_amount, symbol } = unpaidInvoices[currencyId];
          unpaidAmountsByCurrency.push({
            currencyId,
            total_amount,
            symbol,
          });
        }
        this.unpaidAmountsByCurrency = unpaidAmountsByCurrency;
        this.loading = false;
      } catch (error) {
        console.error("Erreur lors du calcul des montants impayés :", error);
        this.loading = false;
      }
    },

    async calculateCurrentMonthExpenses() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/stats/expenses-month",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );
        const currentMonthExpenses = await response.json();
        this.MonthExpenses = currentMonthExpenses.total_expenses;
        this.symbol = currentMonthExpenses.default_currency;
        this.loading = false;
      } catch (error) {
        console.error(
          "Erreur lors de la récupération des dépenses du mois en cours :",
          error
        );
        this.loading = false;
      }
    },
    async fetchCurrentMonthEarnings() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/stats/current-month-earnings",
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

        const currentMonthEarnings = await response.json();
        this.currentMonthEarnings = currentMonthEarnings;
        this.loading = false;
      } catch (error) {
        console.error(
          "Erreur lors de la récupération des revenus du mois en cours :",
          error
        );
        this.loading = false;
      }
    },
    async fetchTotalEarnings() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/stats/total-earnings",
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

        const totalEarnings = await response.json();
        this.totalEarnings = totalEarnings;
        this.loading = false;
      } catch (error) {
        console.error(
          "Erreur lors de la récupération de la totalité des revenus :",
          error
        );
        this.loading = false;
      }
    },
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
};
</script>