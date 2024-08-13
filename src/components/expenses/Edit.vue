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
                v-if="key !== 'category_id' && key !== 'client_id'"
                :type="field.type || 'text'"
                :id="key"
                v-model="editedExpense[key]"
                :class="[
                  'ecwp-input input',
                  'input-bordered',
                  field.class || 'w-full',
                ]"
              />
              <select
                v-if="key === 'category_id'"
                :id="key"
                v-model="editedExpense.category_id"
                :class="[
                  'ecwp-input input',
                  'input-bordered',
                  field.class || 'w-full',
                ]"
              >
                <option
                  v-for="category in categoriesExpenses"
                  :key="category.id"
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>
              <select
                v-if="key === 'client_id'"
                :id="key"
                v-model="editedExpense.client_id"
                :class="[
                  'ecwp-input input',
                  'input-bordered',
                  field.class || 'w-full',
                ]"
              >
                <option
                  v-for="client in clients"
                  :key="client.id"
                  :value="client.id"
                >
                  {{ client.company_name }}
                </option>
              </select>
            </div>
          </div>
          <!-- <div class="ecwp-group form-group relative">
            <label for="attachment" class="ecwp-label form-label"
              >{{
              translations.attached_file
            }}</label
            >
            <input
              type="file"
              id="attachment"
              ref="attachment"
              class="ecwp-input input input-bordered w-full peer"
            />
          </div> -->
          <div class="ecwp-group form-group mt-4">
            <label for="note" class="ecwp-label form-label">{{
              translations.note
            }}</label>
            <textarea
              id="note"
              v-model="editedExpense.notes"
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
    expense: {
      type: Object,
      default: () => ({
        id: null,
        expense_date: "",
        client_id: "",
        amount: "",
        category_id: "",
        notes: "",
      }),
    },
    categories: Array,
    clients: Array,
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      editedExpense: { ...this.expense },
      loadingBtn: false,
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      fields: {
        expense_date: {
          label: translations.expense_date,
        },
        client_id: {
          label: translations.client,
        },
        amount: { label: translations.amount },
        category_id: { label: translations.category },
      },
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
    skeletonItems() {
      return Array.from({ length: 10 }, (_, index) => index);
    },
    categoriesExpenses() {
      return this.categories;
    },
    clients() {
      return this.clients;
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
          `/wp-json/my-easy-compta/v1/expenses/${this.editedExpense.id}`,
          {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(this.editedExpense),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.closeModal();
          this.showToast(data.message, "alert-success");
          this.$emit("expenseEdited");
          this.loadingBtn = false;
        } else {
          const errorMessage = `Error editing expense: ${response.statusText}`;
          this.showToast(errorMessage, "alert-error");
          console.error(errorMessage);
          this.loadingBtn = false;
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error editing expense";
        this.showToast(errorMessage, "alert-error");
        console.error("Error editing expense:", error);
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
    expense: {
      handler(newVal) {
        this.editedExpense = { ...newVal };
      },
      immediate: true,
    },
  },
};
</script>
  