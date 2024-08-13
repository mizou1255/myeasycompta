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
        <table v-if="!loading" class="table w-full">
          <thead>
            <tr>
              <th>{{ translations.invoice_number }}</th>
              <th>{{ translations.client }}</th>
              <th>{{ translations.payment_date }}</th>
              <th>{{ translations.amount }}</th>
              <th>{{ translations.payment_method }}</th>
              <th>{{ translations.note }}</th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody>
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

              <td>{{ payment.method_name }}</td>
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
        <div v-else>
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
    this.fetchPayments();
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
          console.log(this.paymentMethods);
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
      this.fetchPayments(pageNumber);
    },
    perPageChanged() {
      this.fetchPayments();
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
