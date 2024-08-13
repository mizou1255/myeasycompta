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
                :type="field.type || 'text'"
                :id="key"
                v-model="editedClient[key]"
                :class="[
                  'ecwp-input input',
                  'input-bordered',
                  field.class || 'w-full',
                ]"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="ecwp-group form-group">
              <label for="currencyId" class="ecwp-label form-label">{{
                translations.currency
              }}</label>
              <select
                id="currencyId"
                v-model="editedClient.currency_id"
                class="ecwp-input select select-bordered w-full"
              >
                <option
                  v-for="(currencyOption, index) in currencyOptions"
                  :key="index"
                  :value="currencyOption.id"
                >
                  {{ currencyOption.name }} - {{ currencyOption.code }} ({{
                    currencyOption.symbol
                  }})
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
              v-model="editedClient.note"
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
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    client: Object,
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      editedClient: { ...this.client },
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      currencyOptions: [],
      fields: {
        company_name: { label: translations.company_name },
        manager_name: { label: translations.manager_name },
        email: { label: translations.email },
        phone: { label: translations.phone, type: "tel" },
        mobile_phone: { label: translations.mobile, type: "tel" },
        website: { label: translations.website, type: "url" },
        address: { label: translations.address },
        city: { label: translations.city },
        postal_code: { label: translations.postal_code },
        country: { label: translations.country },
      },
      loadingBtn: false,
    };
  },
  mounted() {
    this.fetchOptions();
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
    skeletonItems() {
      return Array.from({ length: 10 }, (_, index) => index);
    },
    currencyOptions() {
      return this.currencyOptions;
    },
  },
  methods: {
    closeModal() {
      const modal = document.getElementById("modal_client_edit");
      modal.close();
    },
    async submitForm() {
      this.loadingBtn = true;
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/clients/${this.editedClient.id}`,
          {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(this.editedClient),
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.loadingBtn = false;
          this.closeModal();
          this.showToast(data.message, "alert-success");
          this.$emit("clientEdited");
        } else {
          const errorMessage = `Error editing client: ${response.statusText}`;
          this.showToast(errorMessage, "alert-error");
          console.error(errorMessage);
          this.loadingBtn = false;
        }
      } catch (error) {
        const errorMessage =
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : "Error editing client";
        this.showToast(errorMessage, "alert-error");
        console.error("Error editing client:", error);
        this.loadingBtn = false;
      }
    },
    async fetchOptions() {
      try {
        const response = await fetch(`/wp-json/my-easy-compta/v1/options`, {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        });

        if (response.ok) {
          const data = await response.json();
          this.currencyOptions = data.currency_options;
        } else {
          throw new Error(`Failed to fetch options: ${response.statusText}`);
        }
      } catch (error) {
        console.error("Error fetching options:", error);
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
    client: {
      handler(newVal) {
        this.editedClient = { ...newVal };
      },
      immediate: true,
    },
  },
};
</script>


