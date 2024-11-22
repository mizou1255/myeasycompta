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
      modal-id="modal_invoice_remove"
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
        <div>
          <router-link :to="{ name: 'InvoiceNew' }">
            <button class="btn btn-primary rounded-full">
              {{ translations.add }}
              <i class="fas fa-plus-circle"></i>
            </button>
          </router-link>
          <span
            v-if="settings.easy_compta_export_addon_active == 1"
            class="ms-2"
          >
            <a
              class="btn btn-outline btn-accent rounded-full hover:text-white"
              href="/wp-admin/admin.php?page=my-easy-compta-export#tab3"
            >
              {{ translations.export }}
              <i class="fas fa-file-export"></i>
            </a>
          </span>
          <span
            v-else
            class="tooltip tooltip-left tooltip-warning ms-2"
            :data-tip="translations.active_export_addon"
          >
            <button class="btn btn-outline btn-accent rounded-full" disabled>
              {{ translations.export }}
              <i class="fas fa-file-export"></i>
            </button>
          </span>
        </div>
      </div>

      <div class="divider mt-2"></div>

      <div class="flex items-center mb-4">
        <label for="perPageSelect" class="mr-2">{{
          translations.display_per_page
        }}</label>
        <select id="perPageSelect" v-model="perPage" @change="perPageChanged">
          <option
            v-for="option in perPageOptions"
            :key="option"
            :value="option"
          >
            {{ option }}
          </option>
        </select>
      </div>
      <div class="overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th>
                <div>{{ translations.invoice_number }}</div>
                <input
                  v-model="filters.invoice_number"
                  @input="fetchInvoicesWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.client }}</div>
                <select
                  v-model="filters.client"
                  @change="fetchInvoicesWithFilters()"
                  class="ecwp-input input-xs input-bordered mt-2"
                >
                  <option value="">{{ translations.all }}</option>
                  <option
                    v-for="client in clients"
                    :key="client.id"
                    :value="client.company_name"
                  >
                    {{ client.company_name }}
                  </option>
                </select>
              </th>
              <th>
                <div>{{ translations.status }}</div>
                <select
                  v-model="filters.status"
                  @change="fetchInvoicesWithFilters()"
                  class="ecwp-input input-xs input-bordered mt-2"
                >
                  <option value="">{{ translations.all_statuses }}</option>
                  <option value="draft">{{ translations.draft }}</option>
                  <option value="unpaid">{{ translations.unpaid }}</option>
                  <option value="paid">{{ translations.paid }}</option>
                </select>
              </th>
              <th>
                <div>{{ translations.total }}</div>
                <input
                  v-model="filters.total_amount"
                  @input="fetchInvoicesWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.due_date }}</div>
                <input
                  v-model="filters.due_date"
                  @input="fetchInvoicesWithFilters()"
                  type="date"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.created_at }}</div>
                <input
                  v-model="filters.created_at"
                  @input="fetchInvoicesWithFilters()"
                  type="date"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody v-if="!loading">
            <tr v-for="invoice in invoices" :key="invoice.id">
              <td>
                <span
                  v-if="invoice.advance == 1"
                  class="badge badge-primary badge-outline badge-sm mr-2"
                  >{{ translations.advance }}</span
                >{{ invoice.invoice_number }}
              </td>
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
                  v-if="invoice.credit == '1'"
                  class="badge badge-error badge-outline badge-sm ms-2"
                  >{{ translations.credit }}</span
                >
              </td>
              <td>
                <div v-if="!loadingPrice">
                  <div
                    v-if="
                      settings.easy_compta_advance_addon_active &&
                      invoice.advance == 1
                    "
                  >
                    {{
                      formatAmount(
                        invoice.advance_amount,
                        invoice.client_currency || default_currency_symbol
                      )
                    }}
                  </div>
                  <div
                    v-if="settings.vat_active == 1"
                    :class="{ 'text-xs': invoice.advance == 1 }"
                  >
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
                  <div v-else :class="{ 'text-xs': invoice.advance == 1 }">
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
        <div v-if="loading">
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
      filteredInvoices: [],
      filters: {
        invoice_number: "",
        client: "",
        status: "",
        total_amount: "",
        due_date: "",
        created_at: "",
      },
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
    this.fetchClients();
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
    fetchInvoicesWithFilters(page = 1) {
      this.loading = true;
      const { perPage, filters } = this;
      const query = new URLSearchParams({
        page,
        per_page: perPage,
        ...filters,
      }).toString();

      fetch(`/wp-json/my-easy-compta/v1/invoices?${query}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
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
          console.error("Error fetching invoices with filters:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchClients() {
      fetch(`/wp-json/my-easy-compta/v1/clients`, {
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
      this.fetchInvoicesWithFilters(pageNumber);
    },
    perPageChanged() {
      this.fetchInvoicesWithFilters();
    },
    formatAmount(amount, currency) {
      return formatAmount(amount, currency, this.settings.currency_position);
    },
    showToast(message, type) {
      showToast(this.toast, message, type);
    },
    confirmDeleteInvoice(invoice) {
      this.selectedInvoice = invoice;
      modal_invoice_remove.showModal();
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
