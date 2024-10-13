<template>
  <div>
    <dialog id="modal_confirm_paid" class="modal" :open="showModal">
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
        <div v-if="status === 'paid'" class="mt-4 mb-4">
          <div class="ecwp-group form-group">
            <label for="payment_method" class="ecwp-label form-label">{{ translations.payment_method }}</label>
            <select 
                id="payment_method"  
                v-model="selectedPaymentMethod" 
                :class="{'input-error': hasPaymentMethodError}"
                class="ecwp-input input input-bordered w-full">
                  <option value="">{{ translations.select }}</option>
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
      paymentMethods: [],
      hasPaymentMethodError: false, 
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  mounted() {
      this.fetchPaymentMethods();
  },
  methods: {
    
    async fetchPaymentMethods() {
      try {
        const response = await fetch('/wp-json/my-easy-compta/v1/payments/methods', {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        });

        if (!response.ok) {
          throw new Error(`Erreur : ${response.status}`);
        }

        const data = await response.json();
        this.paymentMethods = data;
      } catch (error) {
        console.error("Erreur lors de la récupération des méthodes de paiement :", error);
      }
    },
    onConfirm() {
      if (!this.selectedPaymentMethod) {
        this.hasPaymentMethodError = true;
        return;
      }
      this.hasPaymentMethodError = false;
      this.$emit("confirm", this.selectedPaymentMethod);
      this.closeModal();
    },
    onCancel() {
      this.$emit("cancel");
      this.closeModal();
    },
    closeModal() {
      const modal = document.getElementById("modal_confirm_paid");
      modal.close();
    },
  },
};
</script>