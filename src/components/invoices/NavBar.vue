<template>
  <div>
    <div v-if="emailActive == 1">
      <send-invoice-modal
        :loading="loadingModal"
        :show-modal="sendInvoiceModal"
        modal-id="modal_send_invoice"
        :client="client_detail"
        :invoice-id="invoiceInfo.id"
        :subject="subject"
        :content="content"
        @close="sendInvoiceModal = false"
      />
    </div>

    <confirm-modal
      :show-modal="showConfirmModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      :status="selectedStatus"
      @confirm="this.changeInvoiceStatus(selectedStatus)"
      @cancel="showConfirmModal = false"
    />

    <confirm-modal-credit
      :show-modal="showConfirmCreditModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      @confirm="this.addCreditInvoice()"
      @cancel="showConfirmCreditModal = false"
    />
    <div
      v-if="loading"
      class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
    >
      <span class="loading loading-spinner text-primary loading-lg"></span>
    </div>
    <div
      class="navbar bg-base-100 mb-4 shadow-xl rounded-box flex justify-between"
    >
      <div>
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h8m-8 6h16"
              />
            </svg>
          </div>
          <ul
            tabindex="0"
            class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52"
          >
            <li><a>Item</a></li>
            <li>
              <a>Parent</a>
              <ul class="p-2">
                <li><a>Submenu</a></li>
                <li><a>Submenu</a></li>
              </ul>
            </li>
            <li><a>Item</a></li>
          </ul>
        </div>
        <div class="hidden lg:flex gap-2">
          <router-link
            v-if="invoiceInfo.status == 'draft'"
            :to="{
              name: 'InvoiceEdit',
              params: { id: invoiceInfo.id },
            }"
          >
            <button class="btn btn-sm">
              <i class="far fa-edit"></i>
              {{ translations.edit_invoice }}
            </button>
          </router-link>

          <div v-else>
            <button class="btn btn-sm" disabled>
              <i class="far fa-edit"></i>
              {{ translations.edit_invoice }}
            </button>
          </div>

          <template v-if="invoiceInfo.status == 'unpaid'">
            <div>
              <button
                class="btn btn-outline btn-success btn-sm hover:text-white"
                @click="confirmValidateInvoice('paid')"
              >
                <i class="fa fa-check"></i>
                {{ translations.mark_as_paid }}
              </button>
            </div>
          </template>
          <template v-else>
            <div>
              <button
                class="btn btn-outline btn-success btn-sm hover:text-white"
                disabled
              >
                <i class="fa fa-check"></i>
                {{ translations.mark_as_paid }}
              </button>
            </div>
            <div v-if="invoiceInfo.credit != 0 && invoiceInfo.status == 'paid'">
              <button class="btn btn-sm ms-2" disabled>
                <i class="fas fa-undo"></i>
                {{ translations.credit_invoice }}
              </button>
            </div>
            <div v-if="invoiceInfo.credit == 0 && invoiceInfo.status == 'paid'">
              <button class="btn btn-sm ms-2" @click="confirmCreditInvoice()">
                <i class="fas fa-undo"></i>
                {{ translations.credit_invoice }}
              </button>
            </div>
          </template>
        </div>
      </div>
      <button
        v-if="invoiceInfo.status == 'draft'"
        @click="exportToPDF(currencyDefault.currency_id)"
        class="btn btn-outline btn-secondary btn-sm"
        :disabled="loadingPdf"
      >
        <i class="far fa-file-pdf"></i>
        <span>{{ translations.previewPDF }}</span>
        <span
          v-if="loadingPdf"
          class="loading loading-spinner loading-sm"
        ></span>
      </button>
      <div class="flex gap-2">
        <button
          @click.prevent="sendInvoice(invoiceInfo.client_id)"
          class="btn btn-outline btn-primary btn-sm hover:text-white"
          v-if="emailActive == 1 && invoiceInfo.status != 'draft'"
        >
          <i class="fas fa-paper-plane"></i>
          <span v-if="invoiceInfo.sent == 1">{{
            translations.resend_invoice
          }}</span>
          <span v-else>{{ translations.send_invoice }}</span>
          <i class="far fa-envelope" v-if="invoiceInfo.sent == 1"></i>
        </button>

        <div
          v-else-if="emailActive == 1 && invoiceInfo.status == 'draft'"
          class="tooltip tooltip-bottom tooltip-warning"
          :data-tip="translations.draft_cannot_send"
        >
          <button
            click="#"
            class="btn btn-outline btn-primary btn-sm hover:text-white"
            disabled
          >
            <i class="fas fa-paper-plane"></i>
            {{ translations.send_invoice }}
          </button>
        </div>

        <div
          v-else
          class="tooltip tooltip-bottom tooltip-warning"
          :data-tip="translations.active_email_addon"
        >
          <button
            click="#"
            class="btn btn-outline btn-primary btn-sm hover:text-white"
            disabled
          >
            <i class="fas fa-paper-plane"></i>
            {{ translations.send_invoice }}
          </button>
        </div>

        <div v-if="currencyDefault.currency_id !== currencyClient.currency_id">
          <div
            class="dropdown dropdown-end"
            v-if="invoiceInfo.status != 'draft'"
          >
            <div
              tabindex="0"
              role="button"
              class="btn btn-outline btn-success btn-sm"
            >
              <i class="far fa-file-pdf"></i>
              {{ translations.exportToPDF }}
              <span
                v-if="loadingPdf"
                class="loading loading-spinner loading-sm"
              ></span>
            </div>
            <ul
              tabindex="0"
              class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow"
            >
              <li>
                <a
                  @click="exportToPDF(currencyDefault.currency_id)"
                  :disabled="loadingPdf"
                  >{{ translations.invoice_in }}
                  {{ currencyDefault.currency_symbol }}</a
                >
              </li>
              <li>
                <a
                  @click="exportToPDF(currencyClient.currency_id)"
                  :disabled="loadingPdf"
                  >{{ translations.invoice_in }}
                  {{ currencyClient.currency_symbol }}</a
                >
              </li>
            </ul>
          </div>
        </div>
        <div v-else>
          <button
            v-if="invoiceInfo.status != 'draft'"
            @click="exportToPDF(currencyDefault.currency_id)"
            class="btn btn-outline btn-success btn-sm"
            :disabled="loadingPdf"
          >
            <i class="far fa-file-pdf"></i>
            <span v-if="invoiceInfo.status != 'draft'">{{
              translations.exportToPDF
            }}</span>
            <span v-else>{{ translations.previewPDF }}</span>
            <span
              v-if="loadingPdf"
              class="loading loading-spinner loading-sm"
            ></span>
          </button>
        </div>

        <button
          v-if="invoiceInfo.status == 'draft'"
          class="btn btn-outline btn-success btn-sm hover:text-white"
          @click="confirmValidateInvoice('unpaid')"
        >
          <i class="fas fa-check"></i>
          {{ translations.validateInvoice }}
        </button>
      </div>
    </div>
  </div>
</template>
  
<script>
import SendInvoiceModal from "@/components/invoices/Send.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
import ConfirmModalCredit from "@/components/ConfirmAlertCredit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
export default {
  name: "InvoiceNavBar",
  components: {
    SendInvoiceModal,
    RemoveModal,
    ConfirmModal,
    ConfirmModalCredit,
  },
  props: {
    invoiceInfo: Object,
    currencyDefault: Object,
    currencyClient: Object,
    emailActive: String,
  },
  data() {
    return {
      showConfirmModal: false,
      showConfirmCreditModal: false,
      loading: false,
      sendInvoiceModal: false,
      loadingModal: false,
      loadingPdf: false,
      client_detail: null,
      selectedStatus: null,
      subject: "",
      content: "",
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  methods: {
    async changeInvoiceStatus(newStatus, methodPayment) {
      this.loading = true;
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/invoices/update-status",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              id: this.invoiceInfo.id,
              status: newStatus,
              method: methodPayment,
            }),
          }
        );

        const data = await response.json();
        if (data.success) {
          this.invoiceInfo.status = newStatus;
          this.loading = false;
        } else {
          console.error("Failed to update invoice status:", data.message);
          this.loading = false;
        }
      } catch (error) {
        console.error(
          "An error occurred while updating invoice status:",
          error
        );
      }
    },
    async addCreditInvoice() {
      this.loading = true;
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/invoices/credit",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              id: this.invoiceInfo.id,
            }),
          }
        );

        const data = await response.json();
        if (data.success) {
          this.invoiceInfo.credit = 1;
          this.loading = false;
        } else {
          console.error("Failed to update invoice status:", data.message);
          this.loading = false;
        }
      } catch (error) {
        console.error(
          "An error occurred while updating invoice status:",
          error
        );
      }
    },
    exportToPDF(currency) {
      this.loadingPdf = true;
      const invoiceId = this.invoiceInfo.id;
      let url = `/wp-json/my-easy-compta/v1/invoices/pdf/${invoiceId}?currency_id=${currency}`;

      fetch(url, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            this.loadingPdf = false;
            throw new Error("Network response was not ok");
          }
          return response.blob();
        })
        .then((blob) => {
          const url = URL.createObjectURL(blob);
          window.open(url);
          this.loadingPdf = false;
        })
        .catch((error) => {
          console.error("There was a problem with the fetch operation:", error);
          this.loadingPdf = false;
        });
    },
    sendInvoice(clientId) {
      this.loadingModal = true;
      this.sendInvoiceModal = true;
      modal_send_invoice.showModal();
      this.fetchClient(clientId);
      this.fetchSettings();
    },
    fetchClient(clientId) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/clients/details/${clientId}`, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Client not found");
          }
          return response.json();
        })
        .then((data) => {
          this.client_detail = data;
          this.loading = false;
        })
        .catch((error) => {
          console.error("Error fetching client info:", error);
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
          this.subject = settings.email_invoice_subject;
          this.content = settings.email_invoice_content;
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.loading = false;
        this.showToast(error.message, "alert-error");
      }
    },
    confirmValidateInvoice(status) {
      this.selectedStatus = status;
      modal_confirm.showModal();
      this.showConfirmModal = true;
    },
    confirmCreditInvoice() {
      modal_confirm_credit.showModal();
      this.showConfirmCreditModal = true;
    },
  },
};
</script>
  