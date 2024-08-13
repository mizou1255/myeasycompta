<template>
  <div>
    <dialog id="modal_confirm" class="modal" :open="showModal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">{{ modalTitle }}</h3>
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="closeModal"
        >
          ✕
        </button>
        <div class="text-center text-red-400 mb-2">
          <i class="fas fa-exclamation-triangle text-4xl"></i>
        </div>
        <h2 class="text-lg font-semibold text-center">{{ title }}</h2>
        <p class="my-4 text-center text-xl">{{ message }}</p>
        <!-- <div v-if="status === 'paid'" class="mt-4">
          <label
            for="payment-method"
            class="block text-sm font-medium text-gray-700"
            >{{ translations.select_payment_method }}</label
          >
          <select
            id="payment-method"
            v-model="selectedPaymentMethod"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="">{{ translations.select }}</option>
            <option value="1">{{ translations.credit_card }}</option>
            <option value="2">{{ translations.paypal }}</option>
            <option value="3">
              {{ translations.bank_transfer }}
            </option>
            <option value="4">{{ translations.cash }}</option>
          </select> 
        </div>-->
        <div class="flex justify-between space-x-4">
          <button @click="onCancel" class="btn btn-secondary rounded-full">
            {{ cancelText }}
          </button>
          <button
            @click="onConfirm"
            class="btn rounded-full btn-error text-white"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </dialog>
  </div>
</template>
    
    <script>
export default {
  props: {
    isVisible: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: "Confirmation",
    },
    message: {
      type: String,
      default: "Are you sure?",
    },
    confirmText: {
      type: String,
      default: "Confirm",
    },
    cancelText: {
      type: String,
      default: "Cancel",
    },
    status: {
      type: String,
    },
  },
  data() {
    return {
      selectedPaymentMethod: "",
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  methods: {
    onConfirm() {
      this.$emit("confirm", this.selectedPaymentMethod);
      this.closeModal();
    },
    onCancel() {
      this.$emit("cancel");
      this.closeModal();
    },
    closeModal() {
      const modal = document.getElementById("modal_confirm");
      modal.close();
    },
  },
};
</script>