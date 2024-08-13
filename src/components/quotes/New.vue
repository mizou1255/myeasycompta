<template>
  <div class="pt-2 pr-4">
    <Card topMargin="mt-8" modalType="modal_quote_new">
      <div class="flex justify-between items-center mb-4">
        <h2 class="card-title">{{ translations.new_quote }}</h2>
      </div>
      <div class="divider mt-2"></div>
      <div
        v-if="loading"
        class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
      ></div>
      <form @submit.prevent="submitQuote">
        <div class="grid grid-cols-3 gap-4">
          <div class="ecwp-group form-group mb-4">
            <label for="quoteNumber" class="ecwp-label">{{
              translations.quote_number
            }}</label>
            <input
              type="text"
              id="quoteNumber"
              v-model="quote.number"
              class="ecwp-input input input-bordered w-full"
              disabled="disabled"
            />
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="quoteDate" class="ecwp-label">{{
              translations.due_date
            }}</label>
            <input
              type="date"
              id="quoteDate"
              v-model="quote.due_date"
              class="ecwp-input input input-bordered w-full"
              required
            />
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="quoteDate" class="ecwp-label">{{
              translations.provisional_date
            }}</label>
            <input
              type="date"
              id="quoteDate"
              v-model="quote.provisional_start_date"
              class="ecwp-input input input-bordered w-full"
              required
            />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="ecwp-group form-group mb-4">
            <label for="client" class="ecwp-label">{{
              translations.company_name
            }}</label>
            <select
              v-model="quote.client_id"
              class="ecwp-input input input-bordered w-full"
            >
              <option
                v-for="client in clients"
                :value="client.id"
                :key="client.id"
                :selected="client.id === quote.client_id"
              >
                {{ client.company_name }} - {{ client.email }} (
                {{ client.currency_symbol }} )
              </option>
            </select>
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="status" class="ecwp-label">{{
              translations.status
            }}</label>
            <select
              id="status"
              v-model="quote.status"
              class="ecwp-input select select-bordered w-full"
            >
              <option value="draft" selected>
                {{ translations.draft }}
              </option>
              <option value="pending">
                {{ translations.pending }}
              </option>
              <option value="approved">{{ translations.approved }}</option>
              <option value="rejected">{{ translations.rejected }}</option>
            </select>
          </div>
        </div>
        <div class="flex justify-between">
          <button
            type="button"
            class="btn btn-secondary rounded-full"
            @click="cancelAction"
          >
            {{ translations.cancel }}
          </button>
          <button
            type="submit"
            class="btn btn-primary rounded-full"
            :disabled="loadingBtn"
          >
            {{ translations.submit }}
            <span
              v-if="loadingBtn"
              class="loading loading-spinner loading-sm"
            ></span>
          </button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script>
import Card from "@/components/Card.vue";

export default {
  name: "QuoteNew",
  components: {
    Card,
  },
  data() {
    return {
      quote: {
        number: "",
        due_date: "",
        provisional_start_date: "",
        client: "",
        status: "pending",
      },
      loading: false,
      loadingBtn: false,
      clients: [],
      settings: [],
      last_quote_number: "",
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  mounted() {
    this.fetchClients();
    this.fetchSettings();
  },
  methods: {
    cancelAction() {
      this.$router.push("/quotes");
    },
    fetchClients() {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/list-clients`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.clients = data.clients;
        })
        .catch((error) => {
          console.error("Error fetching clients:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    async fetchSettings() {
      try {
        this.loading = true;
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/settings/get",
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        this.loading = false;
        if (response.ok) {
          const settings = await response.json();
          this.settings = settings;
          const lastQuoteNumber = `${this.settings.quote_prefix}_${String(
            this.settings.last_quote_id
          ).padStart(4, "0")}`;
          this.quote.number = lastQuoteNumber;
        } else {
          const error = await response.json();
        }
      } catch (error) {
        this.loading = false;
      }
    },
    submitQuote() {
      this.loadingBtn = true;
      fetch("/wp-json/my-easy-compta/v1/quotes", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
        body: JSON.stringify(this.quote),
      })
        .then((response) => {
          if (!response.ok) {
            this.loadingBtn = false;
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            this.loadingBtn = false;
            this.showToast(data.message, "alert-success");
            this.$router.push({
              name: "QuoteViewDetail",
              params: { id: data.id },
            });
          } else {
            this.loadingBtn = false;
            console.error("Error submitting quote:", data.message);
            this.showToast(data.message, "alert-error");
          }
        })
        .catch((error) => {
          this.loadingBtn = false;
          console.error("Error submitting quote:", error);
          this.showToast(error, "alert-error");
        });
    },
    showToast(message, type) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      setTimeout(() => {
        this.toast.visible = false;
      }, 3000);
    },
  },
};
</script>