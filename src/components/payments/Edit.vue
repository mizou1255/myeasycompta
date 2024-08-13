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
        <h3 class="font-bold text-lg">{{ modalTitle }}</h3>
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="closeModal"
        >
          ✕
        </button>
        <div v-if="loading">
          <!-- Skeleton -->
          <div class="grid grid-cols-2 gap-4">
            <div v-for="n in skeletonItems" :key="n" class="py-2">
              <div class="skeleton h-4 w-full mb-2"></div>
              <div class="skeleton h-4 w-full"></div>
            </div>
          </div>
        </div>
        <form v-else @submit.prevent="submitForm" class="form">
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="(field, key) in fields"
              :key="key"
              class="ecwp-group form-group"
            >
              <label :for="key" class="ecwp-label form-label">{{
                field.label
              }}</label>
              <input
                v-if="key !== 'payment_method'"
                :type="field.type || 'text'"
                :id="key"
                v-model="editedPayment[key]"
                :class="[
                  'ecwp-input input',
                  'input-bordered',
                  field.class || 'w-full',
                ]"
                :disabled="field.disabled"
              />
              <select
                v-else
                :id="key"
                v-model="editedPayment.payment_method_id"
                :class="[
                  'ecwp-input input',
                  'input-bordered',
                  field.class || 'w-full',
                ]"
              >
                <option
                  v-for="method in paymentMethods"
                  :key="method.id"
                  :value="method.id"
                >
                  {{ method.method_name }}
                </option>
              </select>
            </div>
          </div>
          <div class="ecwp-group form-group mt-4">
            <label for="note" class="ecwp-label form-label">{{
              translations.note
            }}</label>
            <textarea
              id="note"
              v-model="editedPayment.notes"
              class="ecwp-input textarea textarea-bordered w-full"
              rows="4"
            ></textarea>
          </div>
          <div class="form-group mt-4 flex justify-end">
            <button
              type="submit"
              class="btn btn-primary rounded-full"
              :disabled="loadingBtn"
            >
              {{ translations.save }}
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
export default {
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
          console.error(errorMessage);
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error editing payment";
        this.showToast(errorMessage, "alert-error");
        console.error("Error editing payment:", error);
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
  