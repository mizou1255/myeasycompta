<template>
  <div>
    <div>
      <send-quote-modal
        :loading="loadingModal"
        :show-modal="sendQuoteModal"
        modal-id="modal_send_quote"
        :client="client_detail"
        :quote-id="quoteInfo.id"
        :subject="subject"
        :content="content"
        @close="sendQuoteModal = false"
      />
    </div>

    <confirm-modal
      :show-modal="showConfirmModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      @confirm="this.convertToInvoice(selectedQuote)"
      @cancel="showConfirmModal = false"
    />
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
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
            <li><a>Item 1</a></li>
            <li>
              <a>Parent</a>
              <ul class="p-2">
                <li><a>Submenu 1</a></li>
                <li><a>Submenu 2</a></li>
              </ul>
            </li>
            <li><a>Item 3</a></li>
          </ul>
        </div>
        <div class="hidden lg:flex gap-2">
          <router-link
            :to="{
              name: 'QuoteEdit',
              params: { id: quoteInfo.id },
            }"
          >
            <button class="btn btn-sm">
              <i class="far fa-edit"></i>{{ translations.edit_quote }}
            </button>
          </router-link>

          <button
            v-if="quoteInfo.status == 'draft'"
            class="btn btn-outline btn-success btn-sm"
            @click="changeQuoteStatus('pending')"
          >
            <i class="fa fa-check"></i>
            {{ translations.validate_quote }}
          </button>

          <button
            v-if="
              quoteInfo.status == 'pending' || quoteInfo.status == 'rejected'
            "
            class="btn btn-outline btn-success btn-sm"
            @click="changeQuoteStatus('approved')"
          >
            <i class="fa fa-check"></i>
            {{ translations.mark_as_accepted }}
          </button>
          <button
            v-if="
              quoteInfo.status == 'pending' || quoteInfo.status == 'approved'
            "
            class="btn btn-outline btn-error btn-sm"
            @click="changeQuoteStatus('rejected')"
          >
            <i class="fa fa-times"></i>
            {{ translations.mark_as_rejected }}
          </button>
          <button
            v-if="quoteInfo.converted != 1 && quoteInfo.status != 'draft'"
            @click="confirmConvertQuote(quoteInfo.id)"
            class="btn btn-sm"
          >
            <i class="fas fa-exchange-alt"></i>
            {{ translations.convertToInvoice }}
          </button>
        </div>
      </div>
      <div class="flex gap-2">
        <button
          @click.prevent="sendQuote(quoteInfo.client_id)"
          class="btn btn-outline btn-primary btn-sm hover:text-white"
          v-if="emailActive == 1"
        >
          <i class="fas fa-paper-plane"></i>
          {{ translations.send_quote }}
          <i class="far fa-envelope" v-if="quoteInfo.sent == 1"></i>
        </button>
        <button
          @click="exportToPDF"
          class="btn btn-outline btn-success btn-sm"
          :disabled="loadingPdf"
        >
          <i class="far fa-file-pdf"></i>
          {{ translations.exportToPDF }}
          <span
            v-if="loadingPdf"
            class="loading loading-spinner loading-sm"
          ></span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import SendQuoteModal from "@/components/quotes/Send.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
export default {
  name: "QuoteNavBar",
  components: {
    SendQuoteModal,
    ConfirmModal,
  },
  props: {
    quoteInfo: Object,
    emailActive: String,
  },
  data() {
    return {
      selectedQuote: null,
      sendQuoteModal: false,
      loadingModal: false,
      loadingPdf: false,
      client_detail: null,
      subject: "",
      content: "",
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

  methods: {
    async changeQuoteStatus(newStatus) {
      try {
        const response = await fetch(
          "/wp-json/my-easy-compta/v1/quotes/update-status",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              id: this.quoteInfo.id,
              status: newStatus,
            }),
          }
        );
        const data = await response.json();

        if (data.success) {
          this.quoteInfo.status = newStatus;
          this.showToast(data.message, "alert-success");
        } else {
          console.error("Failed to update quote status:", data.message);
          this.showToast(data.message, "alert-error");
        }
      } catch (error) {
        console.error("An error occurred while updating quote status:", error);
        this.showToast(error, "alert-error");
      }
    },
    convertToInvoice(quoteId) {
      fetch(`/wp-json/my-easy-compta/v1/quotes/convert-quote/${quoteId}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.showToast(data.message, "alert-success");
            this.$router.push({
              name: "InvoiceViewDetail",
              params: { id: data.id },
            });
          } else {
            this.showToast(data.message, "alert-error");
          }
        })
        .catch((error) => {
          console.error("Error converting quote:", error);
          this.showToast(error.message, "alert-error");
        });
    },
    confirmConvertQuote(quote_id) {
      this.selectedQuote = quote_id;
      modal_confirm.showModal();
      this.showRemoveModal = true;
    },
    exportToPDF() {
      this.loadingPdf = true;
      const quoteId = this.quoteInfo.id;
      fetch(`/wp-json/my-easy-compta/v1/quotes/pdf/${quoteId}`, {
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
          this.loadingPdf = false;
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
    sendQuote(clientId) {
      this.loadingModal = true;
      this.sendQuoteModal = true;
      modal_send_quote.showModal();
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
          this.subject = settings.email_quote_subject;
          this.content = settings.email_quote_content;
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.loading = false;
        this.showToast(error.message, "alert-error");
      }
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