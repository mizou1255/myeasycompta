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
    <dialog id="modal_advance" class="modal" :open="isVisible">
      <div class="modal-box overflow-visible">
        <h3 class="font-bold text-lg">{{ modalTitle }}</h3>
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="closeModal"
        >
          ✕
        </button>
        <h2 class="text-lg font-semibold text-center">{{ title }}</h2>
        <p class="my-4 text-center text-xl">
          Montant total restant : {{ remainingAmount }} {{ currency }}
        </p>
        <p
          v-if="inputValueExceeds && !invoiceSolded"
          class="text-red-500 text-sm"
        >
          Le montant sélectionné dépasse le montant total du devis.
        </p>
        <div class="grid grid-cols-2 gap-4">
          <div v-if="advanceSold == 'no_sold' && !invoiceSolded">
            <div class="flex ecwp-group form-group mb-4">
              <label for="advance-type" class="ecwp-label">Type</label>
              <select
                v-model="selectedType"
                id="advance-type"
                class="ecwp-select ecwp-input input input-bordered w-full"
                :class="{ 'input-error': !selectedType && showError }"
              >
                <option value="percentage">Pourcentage</option>
                <option value="fixed">Montant fixe</option>
              </select>
            </div>
          </div>

          <div
            v-if="selectedType && advanceSold == 'no_sold' && !invoiceSolded"
          >
            <div class="flex ecwp-group form-group mb-4">
              <label for="advance-type" class="ecwp-label">Valeur</label>
              <input
                v-model="inputValue"
                type="text"
                id="advance-value"
                :class="{ 'input-error': !inputValue && showError }"
                class="ecwp-input input input-bordered w-full"
              />
              <div
                class="absolute items-center border rounded-md right-0 top-6"
              >
                <div class="px-3 py-2.5 rounded-l-md bg-base-300 border-r">
                  <span v-if="selectedType === 'percentage'">%</span>
                  <span v-else>{{ currency }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="
            selectedType === 'percentage' && !inputValueExceeds & !invoiceSolded
          "
          class="mt-2"
        >
          <p class="text-sm">
            Montant basé sur le pourcentage:
            {{ calculatedAmount }} {{ currency }}
          </p>
        </div>

        <div class="flex ecwp-group form-group mb-4" v-if="!invoiceSolded">
          <label for="quoteDate" class="ecwp-label">{{
            translations.due_date
          }}</label>
          <VueDatePicker
            class="ecwp-input ecwp-date input input-bordered w-full"
            id="quoteDate"
            v-model="due_date"
            :enable-time-picker="false"
            auto-apply
            :format="formattedDate"
            :min-date="new Date()"
            locale="fr"
            required
            :class="[!inputValue && showError ? 'input-error' : '']"
          />
        </div>

        <div v-if="invoiceSolded">Facture déjà soldé</div>

        <div class="flex justify-between space-x-4 mt-4">
          <button @click="onCancel" class="btn btn-secondary rounded-full">
            {{ cancelText }}
          </button>
          <button
            @click="onConfirm"
            :disabled="inputValueExceeds"
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
import VueDatePicker from "@vuepic/vue-datepicker";
export default {
  components: {
    VueDatePicker,
  },
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
    totalAmount: {
      type: String,
      required: true,
    },
    currency: {
      type: String,
      required: true,
    },
    advanceSold: {
      type: String,
      required: true,
    },
    quoteId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      showError: false,
      selectedType: "",
      inputValue: 0,
      due_date: "",
      invoiceSolded: false,
      establishedAdvances: [],
      loading: false,
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

    formattedDate() {
      return (date) => {
        if (!date) return "";
        const day = date.getDate().toString().padStart(2, "0");
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
      };
    },
    remainingAmount() {
      this.loading = true;
      const totalAdvance = this.establishedAdvances.reduce((sum, advance) => {
        const amount = parseFloat(advance.advance_amount);
        if (isNaN(amount)) {
          console.error(
            "Erreur: advance_amount n'est pas un nombre valide",
            advance.advance_amount
          );
          return sum;
        }

        return sum + amount;
      }, 0);

      return this.totalAmount - totalAdvance;
    },
    inputValueExceeds() {
      if (this.remainingAmount == 0) {
        return true;
      }
      if (this.selectedType === "percentage") {
        return this.calculatedAmount > this.remainingAmount;
      } else if (this.selectedType === "fixed") {
        return this.inputValue > this.remainingAmount;
      }
      return false;
    },
    calculatedAmount() {
      if (this.selectedType === "percentage") {
        return (this.inputValue / 100) * this.remainingAmount;
      }
      return this.inputValue;
    },
  },
  created() {
    this.fetchEstablishedAdvances(this.quoteId);
  },
  methods: {
    fetchEstablishedAdvances(quote_id) {
      const url = `/wp-json/my-easy-compta/v1/advance/${quote_id}`;
      fetch(url, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.establishedAdvances = data;
        })
        .catch((error) => {
          console.error("Error fetching advances:", error);
        });
    },
    onConfirm() {
      if (!this.inputValueExceeds) {
        if (this.advanceSold == "no_sold") {
          if (
            !this.inputValue ||
            !this.due_date ||
            (this.advanceSold == "no_sold" && !this.selectedType)
          ) {
            this.showError = true;
            this.showToast(
              "Veuillez remplir tous les champs obligatoires.",
              "alert-error"
            );
            return;
          }
          this.$emit("confirm", {
            type: this.selectedType,
            value: this.inputValue,
            date: this.due_date,
          });
        } else {
          if (!this.due_date) {
            this.showError = true;
            this.showToast(
              "Veuillez remplir tous les champs obligatoires.",
              "alert-error"
            );
            return;
          }
          this.$emit("confirm", {
            type: "fixed",
            value: this.remainingAmount,
            date: this.due_date,
          });
        }
        this.closeModal();
      }
    },
    onCancel() {
      this.$emit("cancel");
      this.closeModal();
    },
    closeModal() {
      const modal = document.getElementById("modal_advance");
      modal.close();
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
