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

    <remove-modal
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="this.deleteInvoice(selectedInvoice)"
      @cancel="showRemoveModal = false"
    />

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.invoices }}</h2>
        <router-link :to="{ name: 'InvoiceNew' }">
          <button class="btn btn-primary rounded-full">
            {{ translations.add }}
            <i class="fas fa-plus-circle"></i>
          </button>
        </router-link>
      </div>
      <div class="divider mt-2"></div>
      <div class="overflow-x-auto">
        <table v-if="!loading" class="table w-full">
          <thead>
            <tr>
              <th>{{ translations.invoice_number }}</th>
              <th>{{ translations.client }}</th>
              <th>{{ translations.status }}</th>
              <th>{{ translations.total }}</th>
              <th>{{ translations.due_date }}</th>
              <th>{{ translations.created_at }}</th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
              <td>{{ invoice.invoice_number }}</td>
              <td>{{ invoice.client_name }}</td>
              <td>
                <span
                  v-if="invoice.status == 'draft'"
                  class="badge badge-scondary badge-outline badge-sm"
                  >{{ translations.draft }}</span
                >
                <span
                  v-if="invoice.status == 'unpaid'"
                  class="badge badge-warning badge-outline badge-sm"
                  >{{ translations.unpaid }}</span
                >
                <span
                  v-if="invoice.status == 'paid'"
                  class="badge badge-success badge-outline badge-sm"
                  >{{ translations.paid }}</span
                >
                <span
                  v-if="invoice.status == 'cancelled'"
                  class="badge badge-secondary badge-outline badge-sm"
                  >{{ translations.cancelled }}</span
                >
              </td>
              <td>
                <div v-if="!loadingPrice">
                  <div v-if="settings.vat_active == 1">
                    <span
                      v-if="
                        default_currency_symbol == invoice.client_currency ||
                        invoice.client_currency == null
                      "
                      >{{
                        formatAmount(
                          invoice.total_amount,
                          default_currency_symbol
                        )
                      }}</span
                    >
                    <span v-else>{{
                      formatAmount(
                        invoice.total_amount,
                        invoice.client_currency
                      )
                    }}</span>
                  </div>
                  <div v-else>
                    <span
                      v-if="
                        default_currency_symbol == invoice.client_currency ||
                        invoice.client_currency == null
                      "
                      >{{
                        formatAmount(
                          invoice.total_amount,
                          default_currency_symbol
                        )
                      }}</span
                    >
                    <span v-else>{{
                      formatAmount(
                        invoice.total_amount,
                        invoice.client_currency
                      )
                    }}</span>
                  </div>
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>
              <td>
                <div v-if="!loadingPrice">
                  {{ invoice.due_date }}
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>
              <td>
                <div v-if="!loadingPrice">
                  {{ invoice.created }}
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>
              <td class="flex justify-end">
                <span class="lg:tooltip" :data-tip="translations.view">
                  <router-link
                    :to="{
                      name: 'InvoiceViewDetail',
                      params: { id: invoice.id },
                    }"
                  >
                    <button class="btn btn-circle mx-1">
                      <i class="far fa-eye"></i>
                    </button> </router-link
                ></span>
                <span
                  class="lg:tooltip"
                  :data-tip="translations.edit"
                  v-if="invoice.status == 'draft'"
                >
                  <router-link
                    :to="{
                      name: 'InvoiceEdit',
                      params: { id: invoice.id },
                    }"
                  >
                    <button class="btn btn-circle mx-1">
                      <i class="fas fa-pencil-alt"></i>
                    </button> </router-link
                ></span>
                <span
                  class="lg:tooltip"
                  :data-tip="translations.delete"
                  v-if="invoice.status == 'draft'"
                >
                  <button
                    @click="confirmDeleteInvoice(invoice.id)"
                    class="btn btn-circle text-red-500 hover:text-red-700 mx-1"
                  >
                    <i class="far fa-trash-alt"></i></button
                ></span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else>
          <div
            v-for="n in skeletonRows"
            :key="n"
            class="flex flex-col gap-4 w-full"
          >
            <div class="flex gap-4 items-center">
              <div class="skeleton w-16 h-16 rounded-full shrink-0"></div>
              <div class="flex flex-col gap-4 w-full">
                <div class="skeleton h-4 w-full"></div>
                <div class="skeleton h-4 w-full"></div>
              </div>
            </div>
            <div class="divider my-1"></div>
          </div>
        </div>
      </div>
      <!-- Pagination -->
      <div class="join ecwp_pagination mt-6 pt-4">
        <button
          v-for="pageNumber in paginationButtons"
          :key="pageNumber"
          class="join-item btn"
          :class="{
            'btn-disabled': pageNumber === '...' || pageNumber === currentPage,
          }"
          @click="goToPage(pageNumber)"
        >
          {{ pageNumber }}
        </button>
      </div>
    </Card>
  </div>
</template>

<script>
import Card from "@/components/Card.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import { fetchSettings } from "@/api/api";
import {
  calculateVAT,
  calculateWithoutVAT,
  generatePaginationButtons,
  formatAmount,
  showToast,
} from "@/utils/helpers";

export default {
  name: "InvoicesList",
  components: {
    Card,
    RemoveModal,
  },
  data() {
    return {
      invoices: [],
      showInvoiceDetailsModal: false,
      editInvoiceModal: false,
      selectedInvoice: null,
      currentPage: 1,
      totalCount: 0,
      totalPages: 1,
      paginationButtons: [],
      loading: true,
      loadingPrice: true,
      loadingModal: false,
      skeletonRows: 5,
      perPage: 10,
      perPageOptions: [5, 10, 20, 50],
      client_currency: "",
      default_vat: "",
      default_currency: "",
      default_currency_symbol: "",
      settings: {},
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
    };
  },
  created() {
    this.fetchInvoices();
    this.loadSettings();
  },
  methods: {
    fetchInvoices(page = 1) {
      this.loading = true;
      const { perPage } = this;
      fetch(
        `/wp-json/my-easy-compta/v1/invoices?page=${page}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.invoices = data.invoices;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching invoices:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    async loadSettings() {
      try {
        this.loadingPrice = true;
        const { settings, currencySymbol, vatData } = await fetchSettings();
        this.settings = settings;
        this.default_currency_symbol = currencySymbol;
        this.default_vat = vatData;
        this.loadingPrice = false;
      } catch (error) {
        this.showToast(error.message, "alert-error");
        this.loadingPrice = false;
      }
    },
    calculateVAT(amount) {
      return calculateVAT(amount, this.default_vat.rate);
    },
    calculateWithoutVAT(amount) {
      return calculateWithoutVAT(amount);
    },
    generatePaginationButtons() {
      this.paginationButtons = generatePaginationButtons(
        this.currentPage,
        this.totalPages
      );
    },
    goToPage(pageNumber) {
      if (pageNumber === "...") {
        return;
      }
      this.fetchInvoices(pageNumber);
    },
    perPageChanged() {
      this.fetchInvoices();
    },
    formatAmount(amount, currency) {
      return formatAmount(amount, currency, this.settings.currency_position);
    },
    showToast(message, type) {
      showToast(this.toast, message, type);
    },
    confirmDeleteInvoice(invoice) {
      this.selectedInvoice = invoice;
      modal_remove.showModal();
      this.showRemoveModal = true;
    },
    deleteInvoice(invoice_id) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/invoices/delete/${invoice_id}`, {
        method: "DELETE",
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            this.fetchInvoices();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error deleting invoice:", data.statusText);
          }
        })
        .catch((error) => {
          console.log(error.message);
          const errorMessage =
            error && error.message ? error.message : "Error deleting client";
          if (
            errorMessage ===
            "This client cannot be deleted because it has associated data."
          ) {
            this.showToast(errorMessage, "alert-error");
          } else {
            console.error("Error deleting client:", error);
          }
        });
    },
  },
  computed: {
    skeletonItems() {
      return Array.from({ length: 5 }, (_, index) => index);
    },
    totalPages() {
      return Math.ceil(this.totalCount / this.perPage);
    },
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
};
</script>
