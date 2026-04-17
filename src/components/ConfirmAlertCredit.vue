<template>
  <div>
    <dialog id="modal_confirm_credit" class="ecwp-modal-overlay" :open="showModal">
      <div class="ecwp-modal-box max-w-md">
        <!-- Header -->
        <div class="ecwp-modal-header">
          <h3 class="ecwp-modal-title">{{ modalTitle }}</h3>
          <button
            class="ecwp-modal-close"
            @click="closeModal"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="text-center text-red-500 dark:text-red-400 mb-4">
            <i class="fas fa-exclamation-triangle text-4xl"></i>
          </div>
          <h2 class="text-lg font-semibold text-center text-gray-900 dark:text-white mb-2">{{ title }}</h2>
          <p class="text-center text-lg text-gray-600 dark:text-gray-300 mb-6">{{ message }}</p>
          
          <div class="flex justify-center gap-4">
            <button @click="onCancel" class="ecwp-btn ecwp-btn-secondary">
              {{ cancelText }}
            </button>
            <button
              @click="onConfirm"
              class="ecwp-btn ecwp-btn-error"
            >
              {{ confirmText }}
            </button>
          </div>
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
      const modal = document.getElementById("modal_confirm_credit");
      modal.close();
    },
  },
};
</script>