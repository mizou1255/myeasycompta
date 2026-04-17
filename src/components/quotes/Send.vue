<template>
  <div class="p-4 max-w-4xl mx-auto">
    <!-- Toast Notification -->
    <div
      v-if="toast.visible"
      class="ecwp-toast"
    >
      <div :class="['ecwp-toast-content', toast.type === 'error' ? 'ecwp-toast-error' : 'ecwp-toast-success']">
        <i :class="getToastIcon()"></i>
        <span>{{ toast.message }}</span>
      </div>
    </div>

    <!-- Page Header -->
    <header class="ecwp-header mb-8">
      <div>
        <h1 class="ecwp-header-title">{{ translations.send_quote }}</h1>
        <p class="ecwp-header-subtitle">Envoyer le devis par email</p>
      </div>
      <div class="header-actions">
        <button class="ecwp-btn ecwp-btn-ghost" @click="goBack">
          <i class="fas fa-arrow-left mr-2"></i>
          {{ translations.back || 'Retour' }}
        </button>
      </div>
    </header>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="ecwp-card mt-8">
      <div class="ecwp-card-body">
        <div class="grid grid-cols-1 gap-4">
          <div v-for="n in 3" :key="n" class="py-2">
            <div class="skeleton h-4 w-32 mb-2"></div>
            <div class="skeleton h-12 w-full"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div v-else class="ecwp-card mt-8">
      <div class="ecwp-card-body">
        <form @submit.prevent="submitForm" class="form">
          <div class="grid grid-cols-1 gap-6">
            <!-- Client Email -->
            <div class="mb-4">
              <label class="ecwp-label">
                <span class="ecwp-label-text">{{ translations.client }}</span>
              </label>
              <input
                v-model="fields.client_email.value"
                type="email"
                class="ecwp-input w-full"
                disabled
              />
            </div>

            <!-- Subject -->
            <div class="mb-4">
              <label class="ecwp-label">
                <span class="ecwp-label-text">{{ translations.email_subject }}</span>
              </label>
              <input
                v-model="fields.email_subject.value"
                type="text"
                class="ecwp-input w-full"
                required
              />
            </div>

            <!-- Message -->
            <div class="mb-4">
              <label class="ecwp-label">
                <span class="ecwp-label-text">{{ translations.email_content }}</span>
              </label>
              <div class="ecwp-editor-container">
                <vue-editor
                  v-model="fields.email_message.value"
                  :editorToolbar="toolbarOptions"
                ></vue-editor>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-2 mt-8">
            <button
              type="button"
              class="ecwp-btn ecwp-btn-ghost"
              @click="goBack"
              :disabled="loadingBtn"
            >
              <i class="fas fa-times mr-2"></i>
              {{ translations.cancel }}
            </button>
            <button
              type="submit"
              class="ecwp-btn ecwp-btn-primary"
              :disabled="loadingBtn"
            >
              <i v-if="!loadingBtn" class="fas fa-paper-plane mr-2"></i>
              <span v-else class="animate-spin mr-2"><i class="fas fa-spinner"></i></span>
              {{ translations.send }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
  
<script>
import { VueEditor } from "vue3-editor";

export default {
  name: "QuoteSend",
  components: {
    VueEditor,
  },
  data() {
    return {
      loading: true,
      loadingBtn: false,
      quoteId: null,
      client: null,
      toast: {
        visible: false,
        message: "",
        type: "success",
      },
      toolbarOptions: [
        ["bold", "italic", "underline", "strike"],
        ["link"],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        [{ color: [] }, { background: [] }],
        [{ align: [] }],
        ["clean"],
      ],
      fields: {
        client_email: {
          label: "",
          value: "",
          disabled: true,
        },
        email_subject: { 
          label: "", 
          value: "" 
        },
        email_message: {
          label: "",
          value: "",
          type: "textarea",
        },
      },
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  mounted() {
    this.quoteId = this.$route.params.id;
    this.fetchQuoteDetails();
  },
  methods: {
    async fetchQuoteDetails() {
      this.loading = true;
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/quotes/${this.quoteId}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.client = data.client;
          this.fields.client_email.value = data.client.email || "";
          
          // Définir un sujet par défaut
          this.fields.email_subject.value = `Devis ${data.quote.quote_number}`;
          
          // Définir un message par défaut
          this.fields.email_message.value = `
            <p>Bonjour ${data.client.company_name},</p>
            <p>Veuillez trouver ci-joint le devis n° ${data.quote.quote_number}.</p>
            <p>Cordialement,</p>
          `;
        } else {
          this.showToast("Erreur lors du chargement du devis", "error");
        }
      } catch (error) {
        this.showToast("Erreur lors du chargement du devis", "error");
      } finally {
        this.loading = false;
      }
    },
    async submitForm() {
      this.loadingBtn = true;
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/emails/send-email`,
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify({
              type: "quote",
              id: this.quoteId,
              client_email: this.fields.client_email.value,
              email_subject: this.fields.email_subject.value,
              email_message: this.fields.email_message.value,
            }),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.showToast(data.message, "success");
          setTimeout(() => {
            this.goBack();
          }, 2000);
        } else {
          const errorMessage = `Erreur lors de l'envoi : ${response.statusText}`;
          this.showToast(errorMessage, "error");
        }
      } catch (error) {
        const errorMessage = "Erreur lors de l'envoi de l'email";
        this.showToast(errorMessage, "error");
      } finally {
        this.loadingBtn = false;
      }
    },
    goBack() {
      this.$router.push({ name: "Quotes" });
    },
    showToast(message, type) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      setTimeout(() => {
        this.toast.visible = false;
      }, 3000);
    },
    getToastIcon() {
      const icons = {
        success: "fas fa-check-circle",
        error: "fas fa-exclamation-circle",
        warning: "fas fa-exclamation-triangle",
        info: "fas fa-info-circle",
      };
      return icons[this.toast.type] || icons.info;
    },
  },
};
</script>