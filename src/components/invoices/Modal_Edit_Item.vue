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
                  v-model="editedItem[key]"
                  :class="[
                    'ecwp-input input input-bordered',
                    field.class || 'w-full',
                  ]"
                />
              </div>
              <div v-else>
                <label :for="key" class="form-label">{{ field.label }}</label>
                <vue-editor
                  :id="key"
                  v-model="editedItem[key]"
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
import { VueEditor } from "vue3-editor";
export default {
  components: {
    VueEditor,
  },
  props: {
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    item: Object,
  },
  data() {
    return {
      editedItem: { ...this.item },
      loading: this.loading,
      loadingBtn: false,
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      fields: {
        item_name: { label: "Nom de l'item" },
        item_description: { label: "Description de l'item", type: "textarea" },
        quantity: { label: "Quantité", type: "number" },
        vat_rate: { label: "Taux de TVA", type: "number" },
        unit_price: { label: "Prix unitaire", type: "number" },
        discount: { label: "Remise", type: "number" },
      },
    };
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
          `/wp-json/my-easy-compta/v1/invoices/edit-item/${this.editedItem.id}`,
          {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(this.editedItem),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.loadingBtn = false;
          this.closeModal();
          this.showToast(data.message, "alert-success");
          this.$emit("itemEdited");
        } else {
          const errorMessage = `Error editing item: ${response.statusText}`;
          this.showToast(errorMessage, "alert-error");
          console.error(errorMessage);
          this.loadingBtn = false;
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error editing item";
        this.showToast(errorMessage, "alert-error");
        console.error("Error editing item:", error);
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
    increase() {
      this.newItem.quantity++;
    },
    decrease() {
      if (this.newItem.quantity > 1) {
        this.newItem.quantity--;
      }
    },
  },
  watch: {
    item: {
      handler(newVal) {
        this.editedItem = { ...newVal };
      },
      immediate: true,
    },
  },
};
</script>
    