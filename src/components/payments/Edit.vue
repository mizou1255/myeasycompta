<template>
  <div>
    <!-- Toast Notification -->
    <div
      v-if="toast.visible"
      :class="['ecwp-toast', toast.type === 'alert-error' ? 'ecwp-toast-error' : 'ecwp-toast-success']"
    >
      <div class="ecwp-toast-icon">
        <i :class="toast.type === 'alert-error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'"></i>
      </div>
      <div class="ecwp-toast-content">{{ toast.message }}</div>
    </div>

    <dialog :id="modalId" class="ecwp-modal-overlay" :open="showModal">
      <div class="ecwp-modal-box max-w-2xl">
        <div class="ecwp-modal-header">
          <h3 class="ecwp-modal-title">{{ modalTitle }}</h3>
          <button
            class="ecwp-modal-close"
            @click="closeModal"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div v-if="loading" class="p-6">
          <!-- Skeleton -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="n in 4" :key="n" class="space-y-2">
              <div class="ecwp-skeleton h-4 w-24"></div>
              <div class="ecwp-skeleton h-10 w-full"></div>
            </div>
          </div>
        </div>

        <form v-else @submit.prevent="submitForm" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
              v-for="(field, key) in fields"
              :key="key"
            >
              <label :for="key" class="ecwp-label">
                {{ field.label }}
                <span v-if="!field.disabled && key !== 'note'" class="text-red-500">*</span>
              </label>
              
              <input
                v-if="key !== 'payment_method' && key !== 'payment_date'"
                :type="field.type || 'text'"
                :id="key"
                v-model="editedPayment[key]"
                class="ecwp-input"
                :class="{'opacity-60 cursor-not-allowed': field.disabled}"
                :disabled="field.disabled"
              />
              
              <select
                v-else-if="key == 'payment_method'"
                :id="key"
                v-model="editedPayment.payment_method_id"
                class="ecwp-select"
              >
                <option
                  v-for="method in paymentMethods"
                  :key="method.id"
                  :value="method.id"
                >
                  {{ method.method_name }}
                </option>
              </select>
              
              <VueDatePicker
                v-else-if="key == 'payment_date'"
                class="w-full"
                input-class-name="ecwp-input"
                id="invoiceDate"
                v-model="editedPayment.payment_date"
                :enable-time-picker="false"
                auto-apply
                :format="formattedDate"
                :min-date="new Date()"
                locale="fr"
                required
              />
            </div>
          </div>

          <div>
            <label for="note" class="ecwp-label">{{ translations.note }}</label>
            <textarea
              id="note"
              v-model="editedPayment.notes"
              class="ecwp-input h-24 w-full"
              :placeholder="translations.add_note_placeholder || 'Ajouter une note...'"
            ></textarea>
          </div>

          <div class="ecwp-modal-footer">
            <button
              type="button"
              class="ecwp-btn ecwp-btn-secondary"
              @click="closeModal"
            >
              {{ translations.cancel }}
            </button>
            <button
              type="submit"
              class="ecwp-btn ecwp-btn-primary"
              :disabled="loadingBtn"
            >
              <span v-if="loadingBtn" class="animate-spin mr-2">...</span>
              {{ translations.save }}
            </button>
          </div>
        </form>
      </div>
    </dialog>
  </div>
</template>
  
  <script>
import VueDatePicker from "@vuepic/vue-datepicker";
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  components: {
    VueDatePicker,
  },
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    showModal: {
      type: Boolean,
      default: false,
    },
    modalId: {
      type: String,
      required: true,
    },
    modalTitle: {
      type: String,
      default: "",
    },
    payment: {
      type: Object,
      default: () => ({
        id: null,
        invoice_number: "",
        company_name: "",
        amount: "",
        payment_method_id: "",
        payment_date: "",
        note: "",
        payment_methods: [],
      }),
    },
    disabled: {
      type: Boolean,
      default: true,
    },
    methods: Array,
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      editedPayment: { ...this.payment },
      loadingBtn: false,
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      fields: {
        invoice_number: {
          label: translations.invoice_number,
          disabled: this.disabled,
        },
        company_name: { label: translations.client, disabled: this.disabled },
        amount: { label: translations.amount, disabled: false },
        payment_method: { label: translations.payment_method },
        payment_date: { label: translations.payment_date },
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
    paymentMethods() {
      return this.methods;
    },
    skeletonItems() {
      return Array.from({ length: 10 }, (_, index) => index);
    },
  },
  methods: {
    closeModal() {
      const modal = document.getElementById("modal_payment_edit");
      modal.close();
    },
    async submitForm() {
      this.loadingBtn = true;
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/payments/${this.editedPayment.id}`,
          {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(this.editedPayment),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.closeModal();
          this.loadingBtn = false;
          this.showToast(data.message, "alert-success");
          this.$emit("paymentEdited");
        } else {
          this.loadingBtn = false;
          const errorMessage = `Error editing payment: ${response.statusText}`;
          this.showToast(errorMessage, "alert-error");
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error editing payment";
        this.showToast(errorMessage, "alert-error");
        this.loadingBtn = false;
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
  watch: {
    payment: {
      handler(newVal) {
        this.editedPayment = { ...newVal };
      },
      immediate: true,
    },
  },
};
</script>