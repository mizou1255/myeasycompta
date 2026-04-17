<template>
  <div>
    <!-- Toast Notification -->
    <div
      v-if="toast.visible"
      :class="['ecwp-toast', toast.type === 'alert-success' ? 'ecwp-toast-success' : 'ecwp-toast-error']"
    >
      <div class="ecwp-toast-icon">
        <i :class="toast.type === 'alert-success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
      </div>
      <div class="ecwp-toast-content">{{ toast.message }}</div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="ecwp-modal-overlay">
      <div class="ecwp-modal-box max-w-2xl">
        <div class="ecwp-modal-header">
          <h3 class="ecwp-modal-title">{{ modalTitle }}</h3>
          <button
            class="ecwp-btn ecwp-btn-sm ecwp-btn-ghost ecwp-btn-circle"
            @click="closeModal()"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="ecwp-modal-body">
          <div v-if="loading" class="w-full">
            <!-- Skeleton -->
            <div class="grid grid-cols-1 gap-4">
              <div v-for="n in 5" :key="n" class="py-2">
                <div class="skeleton h-4 w-full mb-2 bg-gray-200 dark:bg-dark-hover rounded"></div>
                <div class="skeleton h-8 w-full bg-gray-200 dark:bg-dark-hover rounded"></div>
              </div>
            </div>
          </div>
          <form v-else @submit.prevent="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
              <div
                v-for="(field, key) in fields"
                :key="key"
                class="form-control w-full"
              >
                <template v-if="field.type !== 'textarea'">
                  <label :for="key" class="ecwp-label">
                    {{ field.label }}
                  </label>
                  <input
                    :id="key"
                    :type="field.type || 'text'"
                    v-model="editedDisb[key]"
                    class="ecwp-input"
                    :step="field.type === 'number' ? 'any' : undefined"
                  />
                </template>
                <template v-else>
                  <label :for="key" class="ecwp-label">
                    {{ field.label }}
                  </label>
                  <div class="ecwp-editor-container">
                    <vue-editor
                      :id="key"
                      v-model="editedDisb[key]"
                      :editorToolbar="toolbarOptions"
                    ></vue-editor>
                  </div>
                </template>
              </div>
            </div>
          </form>
        </div>

        <div class="ecwp-modal-footer">
          <button
            type="button"
            class="ecwp-btn ecwp-btn-ghost"
            @click="closeModal()"
          >
            {{ translations.cancel || 'Annuler' }}
          </button>
          <button
            type="button"
            class="ecwp-btn ecwp-btn-primary"
            :disabled="loadingBtn"
            @click="submitForm"
          >
            <span v-if="loadingBtn" class="animate-spin mr-2"><i class="fas fa-spinner"></i></span>
            {{ translations.save }}
          </button>
        </div>
      </div>
    </div>
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
    disb: Object,
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      editedDisb: { ...this.disb },
      loading: this.loading,
      loadingBtn: false,
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      fields: {
        title: { label: translations.item_name },
        description: {
          label: translations.item_description,
          type: "textarea",
        },
        unit_price: { label: translations.unit_price, type: "number" },
      },
    };
  },
  computed: {
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
          `/wp-json/my-easy-compta/v1/invoices/edit-disb/${this.editedDisb.id}`,
          {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(this.editedDisb),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.loadingBtn = false;
          this.closeModal();
          this.showToast(data.message, "alert-success");
          this.$emit("disbEdited");
        } else {
          const errorMessage = `Error editing item: ${response.statusText}`;
          this.showToast(errorMessage, "alert-error");
          this.loadingBtn = false;
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error editing item";
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
    disb: {
      handler(newVal) {
        this.editedDisb = { ...newVal };
      },
      immediate: true,
    },
  },
};
</script>