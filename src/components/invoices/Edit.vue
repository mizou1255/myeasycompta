<template>
  <div class="pt-2 pr-4">
    <Card topMargin="mt-8" modalType="modal_invoice_new">
      <div class="flex justify-between items-center mb-4">
        <h2 class="card-title">{{ translations.edit_invoice }}</h2>
      </div>
      <div class="divider mt-2"></div>
      <div
        v-if="loading"
        class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
      ></div>
      <form @submit.prevent="submitInvoice">
        <div class="grid grid-cols-2 gap-4">
          <div class="ecwp-group form-group mb-4">
            <label for="invoiceNumber" class="ecwp-label">{{
              translations.invoice_number
            }}</label>
            <input
              type="text"
              id="invoiceNumber"
              v-model="invoice.invoice_number"
              class="ecwp-input input input-bordered w-full"
              disabled="true"
            />
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="invoiceDate" class="ecwp-label">{{
              translations.due_date
            }}</label>
            <input
              type="date"
              id="invoiceDate"
              v-model="invoice.due_date"
              class="ecwp-input input input-bordered w-full"
              required
            />
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="client" class="ecwp-label">{{
              translations.company_name
            }}</label>
            <select
              v-model="invoice.client_id"
              class="ecwp-input input input-bordered w-full"
            >
              <option
                v-for="client in clients"
                :value="client.id"
                :key="client.id"
                :selected="client.id === invoice.client_id"
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
              v-model="invoice.status"
              class="ecwp-input select select-bordered w-full"
            >
              <option value="draft" selected>{{ translations.draft }}</option>
              <option value="unpaid">{{ translations.unpaid }}</option>
              <option value="paid">{{ translations.paid }}</option>
              <option value="cancelled">{{ translations.cancelled }}</option>
            </select>
          </div>
          <div v-if="currencyMismatch" class="ecwp-group form-group mb-4">
            <label for="exchangeRate" class="ecwp-label">{{
              translations.exchange_rate
            }}</label>
            <input
              type="text"
              id="exchangeRate"
              v-model="invoice.exchange_rate"
              class="ecwp-input input input-bordered w-full"
              required
            />
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
            {{ translations.save }}
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
  name: "invoiceEdit",
  components: {
    Card,
  },
  data() {
    return {
      invoice: {
        invoice_number: "",
        due_date: "",
        client_id: "",
        client: null,
        status: "paid",
        exchange_rate: 0,
      },
      loading: false,
      loadingBtn: false,
      clients: [],
      settings: [],
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

    currencyMismatch() {
      const selectedClient = this.clients.find(
        (client) => client.id === this.invoice.client_id
      );
      return (
        selectedClient &&
        this.settings.default_currency !== selectedClient.currency_id
      );
    },
  },
  mounted() {
    this.fetchClients();
    this.fetchSettings();
    this.fetchInvoiceDetails();
  },
  methods: {
    cancelAction() {
      this.$router.push("/invoices");
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
        } else {
          const error = await response.json();
          console.error("Error fetching settings:", error);
        }
      } catch (error) {
        this.loading = false;
        console.error("Error fetching settings:", error);
      }
    },
    fetchInvoiceDetails() {
      const invoiceId = this.$route.params.id;
      if (!invoiceId) return;

      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/invoices/${invoiceId}`, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data) {
            this.invoice = data;
            console.log(this.invoice);
          } else {
            console.error("Error fetching invoice details:", data.message);
            this.showToast(data.message, "alert-error");
          }
        })
        .catch((error) => {
          console.error("Error fetching invoice details:", error);
          this.showToast(error, "alert-error");
        })
        .finally(() => {
          this.loading = false;
        });
    },
    setClientById(clientId) {
      this.invoice.client =
        this.clients.find((client) => client.id === clientId) || null;
    },
    handleClientChange() {
      const selectedClient = this.clients.find(
        (client) => client.id === this.invoice.client_id
      );
      if (
        selectedClient &&
        this.settings.default_currency !== selectedClient.currency_id
      ) {
        this.invoice.exchange_rate = 1;
      } else {
        this.invoice.exchange_rate = 0;
      }
    },
    submitInvoice() {
      this.loadingBtn = true;
      fetch(`/wp-json/my-easy-compta/v1/invoices/${this.$route.params.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
        body: JSON.stringify(this.invoice),
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
              name: "InvoiceViewDetail",
              params: { id: data.id },
            });
          } else {
            this.loadingBtn = false;
            console.error("Error submitting invoice:", data.message);
            this.showToast(data.message, "alert-error");
          }
        })
        .catch((error) => {
          this.loadingBtn = false;
          console.error("Error submitting invoice:", error);
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
  