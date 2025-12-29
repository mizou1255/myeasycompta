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
      @confirm="changeInvoiceStatus('unpaid')"
      @cancel="showConfirmModal = false"
    />

    <confirm-modal-paid
      :show-modal="showConfirmModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      :status="selectedStatus"
      @confirm="changeInvoiceStatusWithPaymentMethod"
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
    <div v-if="qrCodeActive == 1 && showQrCodeModal" class="modal modal-open">
      <div class="modal-box">
        <h3 class="font-bold text-lg">{{ translations.download_qr_code }}</h3>
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="closeQrCodeModal()"
        >
          ✕
        </button>

        <div class="mb-4">
          <img
            :src="qrCodeSrc"
            alt="QR Code"
            class="max-w-full h-auto mx-auto"
          />
        </div>

        <div class="flex justify-end space-x-4">
          <button @click="downloadQRCode" class="btn btn-primary">
            {{ translations.download_qr_code }}
          </button>
        </div>
      </div>
    </div>
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

          <template v-if="invoiceInfo.status == 'unpaid' && !noItems">
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
            <div
              v-if="
                invoiceInfo.credit != 0 &&
                invoiceInfo.status == 'paid' &&
                !noItems
              "
            >
              <button class="btn btn-sm ms-2" disabled>
                <i class="fas fa-undo"></i>
                {{ translations.credit_invoice }}
              </button>
            </div>
            <div
              v-if="
                invoiceInfo.credit == 0 &&
                invoiceInfo.status == 'paid' &&
                !noItems
              "
            >
              <button class="btn btn-sm ms-2" @click="confirmCreditInvoice()">
                <i class="fas fa-undo"></i>
                {{ translations.credit_invoice }}
              </button>
            </div>
          </template>

          <!-- Bouton pour créer une facture récurrente -->
          <div
            v-if="recurringActive == 1"
            class="tooltip tooltip-bottom"
            :data-tip="translations.create_recurring_invoice || 'Créer une facture récurrente'"
          >
            <button
              @click="createRecurringInvoice"
              class="btn btn-outline btn-info btn-sm hover:text-white"
            >
              <i class="fas fa-redo"></i>
              {{ translations.create_recurring_invoice || 'Facture récurrente' }}
            </button>
          </div>
          <div
            v-else
            class="tooltip tooltip-bottom tooltip-warning"
            :data-tip="translations.active_recurring_addon || 'Achetez l\'addon Factures Récurrentes pour automatiser vos factures récurrentes'"
          >
            <button
              class="btn btn-outline btn-info btn-sm"
              disabled
            >
              <i class="fas fa-redo"></i>
              {{ translations.create_recurring_invoice || 'Facture récurrente' }}
            </button>
          </div>
        </div>
      </div>
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

        <div v-if="qrCodeActive == 1">
          <button
            v-if="invoiceInfo.status == 'unpaid'"
            class="btn btn-outline btn-accent btn-sm"
            @click="generateQRCode"
          >
            <i class="fas fa-qrcode"></i> {{ translations.generate_qrcode }}
          </button>
          <button
            v-else
            click="#"
            class="btn btn-outline btn-primary btn-sm hover:text-white"
            disabled
          >
            <i class="fas fa-qrcode"></i>
            {{ translations.generate_qrcode }}
          </button>
        </div>

        <div
          v-else
          class="tooltip tooltip-bottom tooltip-warning"
          :data-tip="translations.active_qrcode_addon"
        >
          <button
            click="#"
            class="btn btn-outline btn-primary btn-sm hover:text-white"
            disabled
          >
            <i class="fas fa-qrcode"></i>
            {{ translations.generate_qrcode }}
          </button>
        </div>

        <!-- Dropdown unifié pour PDF et Factur-X -->
        <div
          v-if="invoiceInfo.status != 'draft'"
          class="dropdown dropdown-end"
        >
          <div
            tabindex="0"
            role="button"
            class="btn btn-outline btn-success btn-sm"
          >
            <i class="far fa-file-pdf"></i>
            {{ translations.exportToPDF }}
            <i class="fas fa-chevron-down ml-1"></i>
            <span
              v-if="loadingPdf || loadingPdfFacturX"
              class="loading loading-spinner loading-sm ml-1"
            ></span>
          </div>
          <ul
            tabindex="0"
            class="dropdown-content menu bg-base-100 rounded-box z-[1] w-56 p-2 shadow"
          >
            <li>
              <a
                @click="exportToPDF(currencyDefault.currency_id)"
                :disabled="loadingPdf"
              >
                <i class="far fa-file-pdf"></i>
                PDF {{ translations.invoice_in }}
                {{ currencyDefault.currency_symbol }}
              </a>
            </li>
            <li v-if="currencyDefault.currency_id !== currencyClient.currency_id">
              <a
                @click="exportToPDF(currencyClient.currency_id)"
                :disabled="loadingPdf"
              >
                <i class="far fa-file-pdf"></i>
                PDF {{ translations.invoice_in }}
                {{ currencyClient.currency_symbol }}
              </a>
            </li>
            <li>
              <a
                @click="exportToPDFFacturX(currencyDefault.currency_id)"
                :disabled="loadingPdfFacturX"
              >
                <i class="fas fa-file-invoice"></i>
                Factur-X {{ translations.invoice_in }}
                {{ currencyDefault.currency_symbol }}
              </a>
            </li>
            <li v-if="currencyDefault.currency_id !== currencyClient.currency_id">
              <a
                @click="exportToPDFFacturX(currencyClient.currency_id)"
                :disabled="loadingPdfFacturX"
              >
                <i class="fas fa-file-invoice"></i>
                Factur-X {{ translations.invoice_in }}
                {{ currencyClient.currency_symbol }}
              </a>
            </li>
          </ul>
        </div>
        <button
          v-else
          @click="exportToPDF(currencyDefault.currency_id)"
          class="btn btn-outline btn-secondary btn-sm"
          :disabled="loadingPdf"
        >
          <i class="far fa-file-pdf"></i>
          {{ translations.previewPDF }}
          <span
            v-if="loadingPdf"
            class="loading loading-spinner loading-sm"
          ></span>
        </button>

        <button
          v-if="invoiceInfo.status == 'draft' && !noItems"
          class="btn btn-outline btn-success btn-sm hover:text-white"
          @click="confirmValidateInvoice('unpaid')"
        >
          <i class="fas fa-check"></i>
          {{ translations.validateInvoice }}
        </button>

        <div
          v-if="invoiceInfo.status == 'draft' && noItems"
          class="tooltip tooltip-left tooltip-warning"
          :data-tip="translations.min_article"
        >
          <button
            click="#"
            class="btn btn-outline btn-primary btn-sm hover:text-white"
            disabled
          >
            <i class="fas fa-check"></i>
            {{ translations.validateInvoice }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
  
<script>
import SendInvoiceModal from "@/components/invoices/Send.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
import ConfirmModalPaid from "@/components/ConfirmAlertPaid.vue";
import ConfirmModalCredit from "@/components/ConfirmAlertCredit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
export default {
  name: "InvoiceNavBar",
  components: {
    SendInvoiceModal,
    RemoveModal,
    ConfirmModal,
    ConfirmModalPaid,
    ConfirmModalCredit,
  },
  props: {
    invoiceInfo: Object,
    currencyDefault: Object,
    currencyClient: Object,
    emailActive: String,
    qrCodeActive: String,
    recurringActive: String,
    totalAmount: String,
    noItems: Boolean,
  },
  data() {
    return {
      showConfirmModal: false,
      showConfirmCreditModal: false,
      loading: false,
      sendInvoiceModal: false,
      loadingModal: false,
      loadingPdf: false,
      loadingPdfFacturX: false,
      client_detail: null,
      selectedStatus: null,
      subject: "",
      content: "",
      showQrCodeModal: false,
      qrCodeSrc: "",
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  methods: {
    changeInvoiceStatusWithPaymentMethod(selectedPaymentMethod) {
      const status = this.selectedStatus;
      this.changeInvoiceStatus(status, selectedPaymentMethod);
      this.showConfirmModal = false;
    },
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
    exportToPDFFacturX(currency) {
      this.loadingPdfFacturX = true;
      const invoiceId = this.invoiceInfo.id;
      let url = `/wp-json/my-easy-compta/v1/invoices/pdf-facturx/${invoiceId}?currency_id=${currency}`;

      fetch(url, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            this.loadingPdfFacturX = false;
            throw new Error("Network response was not ok");
          }
          return response.blob();
        })
        .then((blob) => {
          const url = URL.createObjectURL(blob);
          const link = document.createElement("a");
          link.href = url;
          link.download = `facture_${this.invoiceInfo.invoice_number || this.invoiceInfo.id}_facturx.pdf`;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          URL.revokeObjectURL(url);
          this.loadingPdfFacturX = false;
        })
        .catch((error) => {
          console.error("There was a problem with the fetch operation:", error);
          this.loadingPdfFacturX = false;
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
      if (status == "unpaid") {
        modal_confirm.showModal();
      } else if (status == "paid") {
        modal_confirm_paid.showModal();
      }

      this.showConfirmModal = true;
    },
    confirmCreditInvoice() {
      modal_confirm_credit.showModal();
      this.showConfirmCreditModal = true;
    },

    async generateQRCode() {
      this.loading = true;
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/generate-qrcode",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              invoice_ref:
                this.invoiceInfo.invoice_number ||
                `Facture #${this.invoiceInfo.number}`,
              price: parseFloat(this.invoiceInfo.total_amount),
            }),
          }
        );

        const data = await response.json();

        if (response.ok && data.qr_code) {
          this.qrCodeSrc = data.qr_code;
          this.showQrCodeModal = true;
        } else {
          console.error(
            "Erreur lors de la génération du QR Code :",
            data.message
          );
        }
      } catch (error) {
        console.error(
          "Erreur lors de l'appel à l'API pour générer le QR code :",
          error
        );
      } finally {
        this.loading = false;
      }
    },
    closeQrCodeModal() {
      this.showQrCodeModal = false;
    },

    downloadQRCode() {
      const link = document.createElement("a");
      link.href = this.qrCodeSrc;
      link.download = `qr_code_${
        this.invoiceInfo.invoice_number || this.invoiceInfo.number
      }.png`;
      link.click();
    },
    createRecurringInvoice() {
      // Rediriger vers la page des factures récurrentes avec les paramètres pré-remplis
      const params = new URLSearchParams({
        from_invoice: this.invoiceInfo.id,
        client_id: this.invoiceInfo.client_id,
        template_invoice_id: this.invoiceInfo.id,
        invoice_number: this.invoiceInfo.invoice_number || `#${this.invoiceInfo.number}`,
      });
      window.location.href = `/wp-admin/admin.php?page=my-easy-compta-recurring-invoices&${params.toString()}`;
    },
  },
};
</script>
  