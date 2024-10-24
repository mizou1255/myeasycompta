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
    <payment-edit-modal
      :loading="loadingModal"
      :show-modal="editPaymentModal"
      modal-id="modal_payment_edit"
      :modal-title="translations.edit_payment"
      :payment="selectedPayment"
      :methods="paymentMethods"
      @close="editPaymentModal = false"
      @paymentEdited="fetchPayments"
    />
    <remove-modal
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="this.deletePayment(selectedPayment)"
      @cancel="showRemoveModal = false"
    />
    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.payments }}</h2>

        <div v-if="settings.easy_compta_export_addon_active == 1">
          <a
            class="btn btn-outline btn-accent rounded-full"
            href="/wp-admin/admin.php?page=my-easy-compta-export#tab4"
          >
            {{ translations.export }}
            <i class="fas fa-file-export"></i>
          </a>
        </div>
        <div
          v-else
          class="tooltip tooltip-left tooltip-warning"
          :data-tip="translations.active_export_addon"
        >
          <button class="btn btn-outline btn-accent rounded-full" disabled>
            {{ translations.export }}
            <i class="fas fa-file-export"></i>
          </button>
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
                  @input="fetchPaymentsWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.client }}</div>
                <select
                  v-model="filters.client"
                  @change="fetchPaymentsWithFilters()"
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
                <div>{{ translations.payment_date }}</div>
                <input
                  v-model="filters.payment_date"
                  @input="fetchPaymentsWithFilters()"
                  type="date"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.amount }}</div>
                <input
                  v-model="filters.total_amount"
                  @input="fetchPaymentsWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.payment_method }}</div>
                <select
                  v-model="filters.payment_method"
                  @change="fetchPaymentsWithFilters()"
                  class="ecwp-input input-xs input-bordered mt-2"
                >
                  <option value="">{{ translations.all }}</option>
                  <option
                    v-for="method in payments_methods"
                    :key="method.id"
                    :value="method.method_name"
                  >
                    {{ method.method_name }}
                  </option>
                </select>
              </th>
              <th class="align-top">{{ translations.note }}</th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody v-if="!loading">
            <tr v-for="payment in payments" :key="payment.id">
              <td>{{ payment.invoice_number }}</td>
              <td>{{ payment.company_name }}</td>
              <td>{{ payment.payment_date }}</td>
              <td>
                <div v-if="!loadingPrice">
                  <span>{{
                    formatAmount(payment.amount, default_currency_symbol)
                  }}</span>
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>

              <td>{{ payment.payment_method }}</td>
              <td>{{ payment.notes }}</td>
              <td class="flex justify-end">
                <span class="lg:tooltip" :data-tip="translations.edit">
                  <button
                    class="btn btn-circle mx-1"
                    @click="editPayment(payment.id)"
                  >
                    <i class="fas fa-pencil-alt"></i></button
                ></span>

                <span class="lg:tooltip" :data-tip="translations.delete">
                  <button
                    @click="confirmDeletePayment(payment.id)"
                    class="btn btn-circle text-red-500 hover:text-red-700 mx-1"
                  >
                    <i class="far fa-trash-alt"></i></button
                ></span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading">
          <!-- Skeleton loader -->
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

        <!-- Pagination -->
        <div class="join ecwp_pagination mt-6 pt-4">
          <button
            v-for="pageNumber in paginationButtons"
            :key="pageNumber"
            class="join-item btn"
            :class="{
              'btn-disabled':
                pageNumber === '...' || pageNumber === currentPage,
            }"
            @click="goToPage(pageNumber)"
          >
            {{ pageNumber }}
          </button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script>
import Card from "@/components/Card.vue";
import PaymentEditModal from "@/components/payments/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import { fetchSettings } from "@/api/api";
import {
  generatePaginationButtons,
  formatAmount,
  showToast,
} from "@/utils/helpers";

export default {
  name: "Payments",
  components: {
    Card,
    PaymentEditModal,
    RemoveModal,
  },
  data() {
    return {
      payments: [],
      filteredPayments: [],
      filters: {
        invoice_number: "",
        client: "",
        payment_date: "",
        total_amount: "",
        payment_methods: "",
      },
      clients: [],
      payments_methods: [],
      paymentMethods: [],
      paymentForm: {
        invoice_id: "",
        client_id: "",
        amount: "",
        payment_method_id: "",
        payment_date: "",
      },
      editPaymentModal: false,
      selectedPayment: null,
      currentPage: 1,
      totalPages: 1,
      paginationButtons: [],
      loading: true,
      loadingPrice: true,
      loadingModal: false,
      showRemoveModal: false,
      skeletonRows: 5,
      perPage: 10,
      perPageOptions: [5, 10, 20, 50],
      settings: [],
      default_currency_symbol: "",
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
    };
  },
  created() {
    this.fetchPaymentsWithFilters();
    this.fetchClients();
    this.fetchPaymentMethods();
    this.loadSettings();
  },
  methods: {
    fetchPayments(page = 1) {
      this.loading = true;
      const { perPage } = this;
      fetch(
        `/wp-json/my-easy-compta/v1/payments?page=${page}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.payments = data.payments;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = data.page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching payments:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchPaymentsWithFilters(page = 1) {
      this.loading = true;
      const { perPage, filters } = this;
      const query = new URLSearchParams({
        page,
        per_page: perPage,
        ...filters,
      }).toString();

      fetch(`/wp-json/my-easy-compta/v1/payments?${query}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.payments = data.payments;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching payments with filters:", error);
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
    fetchPaymentMethods() {
      fetch(`/wp-json/my-easy-compta/v1/payments/methods`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.payments_methods = data;
        })
        .catch((error) => {
          console.error("Error fetching payment Methods:", error);
        });
    },
    editPayment(payment) {
      this.loadingModal = true;
      this.editPaymentModal = true;
      modal_payment_edit.showModal();
      this.fetchPaymentDetails(payment);
    },
    fetchPaymentDetails(paymentId) {
      fetch(`/wp-json/my-easy-compta/v1/payments/details/${paymentId}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.selectedPayment = data;
          this.paymentMethods = data.payment_methods;
          this.loadingModal = false;
        })
        .catch((error) => {
          console.error("Error fetching payment details:", error);
          this.loadingModal = false;
        });
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
      this.fetchPaymentsWithFilters(pageNumber);
    },
    perPageChanged() {
      this.fetchPaymentsWithFilters();
    },
    formatAmount(amount, currency) {
      return formatAmount(amount, currency, this.settings.currency_position);
    },
    showToast(message, type) {
      showToast(this.toast, message, type);
    },
    closePaymentModal() {
      this.showPaymentModal = false;
    },
    confirmDeletePayment(payment) {
      this.selectedPayment = payment;
      modal_remove.showModal();
      this.showRemoveModal = true;
    },
    deletePayment(paymentId) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/payments/${paymentId}`, {
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
            this.fetchPayments();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error deleting payment:", data.statusText);
          }
        })
        .catch((error) => {
          console.error("Error deleting payment:", error);
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
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
};
</script>
