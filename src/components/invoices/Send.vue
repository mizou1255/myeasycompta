<template>
  <div>
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>
    <dialog :id="modalId" class="modal" :open="showModal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">{{ translations.send_invoice }}</h3>
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="closeModal()"
        >
          ✕
        </button>
        <div v-if="loading">
          <!-- Skeleton -->
          <div class="grid grid-cols-1 gap-4">
            <div v-for="n in skeletonItems" :key="n" class="py-2">
              <div class="skeleton h-4 w-full mb-2"></div>
              <div class="skeleton h-4 w-full"></div>
            </div>
          </div>
        </div>
        <form v-else @submit.prevent="submitForm" class="form">
          <div class="grid grid-cols-1 gap-4">
            <div
              v-for="(field, key) in fields"
              :key="key"
              class="ecwp-group form-group"
            >
              <div
                v-if="field.type !== 'textarea'"
                :type="field.type || 'text'"
              >
                <label :for="key" class="ecwp-label form-label">{{
                  field.label
                }}</label>
                <input
                  :id="key"
                  :class="[
                    'ecwp-input input input-bordered',
                    field.class || 'w-full',
                  ]"
                  :value="field.value"
                  :disabled="field.disabled"
                  @input="updateFieldValue(key, $event.target.value)"
                />
              </div>
              <div v-else>
                <label :for="key" class="form-label">{{ field.label }}</label>
                <vue-editor
                  v-model="field.value"
                  :editorToolbar="toolbarOptions"
                ></vue-editor>
              </div>
            </div>
          </div>
          <div class="form-group mt-4 flex justify-end">
            <button
              type="submit"
              class="btn btn-primary rounded-full"
              :disabled="loadingBtn"
            >
              {{ translations.send }}
              <span
                v-if="loadingBtn"
                class="loading loading-spinner loading-sm"
              ></span>
            </button>
          </div>
        </form>
      </div>
    </dialog>
  </div>
</template>
  
  <script>
import { VueEditor } from "vue3-editor";
export default {
  name: "sendInvoice",
  components: {
    VueEditor,
  },
  props: {
    showModal: Boolean,
    modalId: String,
    client: Object,
    invoiceId: Number,
    loading: Boolean,
    subject: String,
    content: String,
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      loading: false,
      loadingBtn: false,

      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      toolbarOptions: [
        ["bold", "italic", "underline", "strike"],
        ["link"],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        [{ color: [] }, { background: [] }],
        [{ align: [] }],
        [{ align: "right" }, { align: "center" }, { align: "justify" }],
        ["clean"],
      ],
      fields: {
        client_email: {
          label: translations.client,
          value: "",
          disabled: true,
        },
        email_subject: { label: translations.email_subject, value: "" },
        email_message: {
          label: translations.email_content,
          value: "",
          type: "textarea",
        },
      },
    };
  },
  watch: {
    client: {
      immediate: true,
      handler(newClient) {
        this.fields.client_email.value = newClient?.email || "";
      },
    },
    subject: {
      immediate: true,
      handler(newSubject) {
        this.fields.email_subject.value = newSubject || "";
      },
    },
    content: {
      immediate: true,
      handler(newContent) {
        this.fields.email_message.value = newContent || "";
      },
    },
  },
  computed: {
    skeletonItems() {
      return Array.from({ length: 10 }, (_, index) => index);
    },
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  methods: {
    closeModal() {
      const modal = document.getElementById(this.modalId);
      modal.close();
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
              type: "invoice",
              id: this.invoiceId,
              client_email: this.client.email,
              email_subject: this.fields.email_subject.value,
              email_message: this.fields.email_message.value,
            }),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.loadingBtn = false;
          this.closeModal();
          this.showToast(data.message, "alert-success");
        } else {
          const errorMessage = `Error sending email: ${response.statusText}`;
          this.showToast(errorMessage, "alert-error");
          console.error(errorMessage);
          this.loadingBtn = false;
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error sending email";
        this.showToast(errorMessage, "alert-error");
        console.error("Error sending email:", error);
        this.loadingBtn = false;
      }
    },
    updateFieldValue(key, value) {
      this.fields[key].value = value;
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
  