<template>
  <div class="pt-2 pr-4">
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>
    <Card topMargin="mt-8" modalType="modal_invoice_new">
      <div class="flex justify-between items-center mb-4">
        <h2 class="card-title">{{ translations.new_invoice }}</h2>
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
              v-model="invoice.number"
              class="ecwp-input input input-bordered w-full"
              disabled="disabled"
            />
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="invoiceDate" class="ecwp-label">{{
              translations.due_date
            }}</label>
            <VueDatePicker
              class="ecwp-input ecwp-date input input-bordered w-full"
              :class="[!invoice.due_date && showError ? 'input-error' : '']"
              id="invoiceDate"
              v-model="invoice.due_date"
              :enable-time-picker="false"
              auto-apply
              :format="formattedDate"
              :min-date="new Date()"
              locale="fr"
            />
          </div>
          <div class="mb-4">
            <div class="flex gap-2 items-end">
              <button type="button" class="btn btn-primary" @click="AddNew">
                <i class="fas fa-plus"></i>
              </button>
              <div class="ecwp-group form-group w-full">
                <label for="client" class="ecwp-label">{{
                  translations.company_name
                }}</label>
                <model-select
                  v-model="invoice.client_id"
                  :options="clientOptions"
                  label="text"
                  track-by="value"
                  :placeholder="translations.select"
                  class="ecwp-input input input-bordered w-full"
                  :class="[
                    !invoice.client_id && showError ? 'input-error' : '',
                  ]"
                />
              </div>
            </div>
          </div>
          <div class="ecwp-group form-group mb-4">
            <label for="status" class="ecwp-label">{{
              translations.status
            }}</label>
            <select
              id="status"
              v-model="invoice.status"
              class="ecwp-input select select-bordered w-full"
              :class="[!invoice.status && showError ? 'input-error' : '']"
            >
              <option value="draft">{{ translations.draft }}</option>
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
            {{ translations.submit }}
            <span
              v-if="loadingBtn"
              class="loading loading-spinner loading-sm"
            ></span>
          </button>
        </div>
      </form>
    </Card>
    <AddClientModal @clientAdded="fetchClients" />
  </div>
</template>

<script>
import Card from "@/components/Card.vue";
import { ModelSelect } from "vue-search-select";
import VueDatePicker from "@vuepic/vue-datepicker";
import AddClientModal from "@/components/clients/Add.vue";

export default {
  name: "InvoiceNew",
  components: {
    Card,
    ModelSelect,
    VueDatePicker,
    AddClientModal,
  },
  data() {
    return {
      invoice: {
        invoice_number: "",
        due_date: "",
        client_id: "",
        client: null,
        status: "",
        exchange_rate: 0,
      },
      showError: false,
      loading: false,
      loadingBtn: false,
      clients: [],
      clientOptions: [],
      settings: [],
      last_invoice_number: "",
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
    formattedDate() {
      return (date) => {
        if (!date) return "";
        const day = date.getDate().toString().padStart(2, "0");
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
      };
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
  },
  methods: {
    AddNew() {
      modal_clients.showModal();
    },
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
          this.clientOptions = this.clients.map((client) => ({
            value: client.id,
            text: `${client.company_name} - ${client.email} (${client.currency_symbol})`,
          }));
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
          const lastInvoiceNumber = `${this.settings.invoice_prefix}_${String(
            this.settings.last_invoice_id
          ).padStart(4, "0")}`;
          this.invoice.number = lastInvoiceNumber;
        } else {
          const error = await response.json();
        }
      } catch (error) {
        this.loading = false;
      }
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
      if (
        !this.invoice.due_date ||
        !this.invoice.client_id ||
        !this.invoice.status
      ) {
        this.showError = true;
        this.showToast(
          "Veuillez remplir tous les champs obligatoires.",
          "alert-error"
        );
        return;
      }
      this.loadingBtn = true;
      fetch("/wp-json/my-easy-compta/v1/invoices", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
        body: JSON.stringify(this.invoice),
      })
        .then((response) => {
          if (!response.ok) {
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
            console.error("Error submitting invoice:", data.message);
            this.showToast(data.message, "alert-error");
            this.loadingBtn = false;
          }
        })
        .catch((error) => {
          console.error("Error submitting invoice:", error);
          this.showToast(error, "alert-error");
          this.loadingBtn = false;
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
