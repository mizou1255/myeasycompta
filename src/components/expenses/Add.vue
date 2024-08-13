<template>
  <div>
    <dialog id="modal_expenses" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">Ajouter une dépense</h3>
        <form @submit.prevent="submitForm">
          <button
            class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
            @click="closeModal"
          >
            ✕
          </button>
          <!-- Amount -->
          <div class="ecwp-group form-group relative">
            <label for="amount" class="ecwp-label form-label">Montant</label>
            <input
              type="number"
              id="amount"
              v-model="formData.amount"
              class="ecwp-input input input-bordered w-full peer"
              placeholder="Montant"
              required
            />
          </div>
          <!-- Expense Date -->
          <div class="ecwp-group form-group relative">
            <label for="expense_date" class="ecwp-label form-label"
              >Date de dépense</label
            >
            <input
              type="date"
              id="expense_date"
              v-model="formData.expense_date"
              class="ecwp-input input input-bordered w-full peer"
              placeholder="Date de dépense"
              required
            />
          </div>
          <!-- Client -->
          <div class="ecwp-group form-group relative">
            <label for="client_id" class="ecwp-label form-label">{{
              translations.client
            }}</label>
            <select
              id="client_id"
              v-model="formData.client_id"
              class="ecwp-input input input-bordered w-full peer"
              required
            >
              <option value="">{{ translations.select_client }}</option>
              <option
                v-for="client in options.clients"
                :key="client.id"
                :value="client.id"
              >
                {{ client.company_name }}
              </option>
            </select>
          </div>
          <!-- Category -->
          <div class="ecwp-group form-group relative">
            <label for="category_id" class="ecwp-label form-label">{{
              translations.category
            }}</label>
            <select
              id="category_id"
              v-model="formData.category_id"
              class="ecwp-input input input-bordered w-full peer"
              required
            >
              <option value="">{{ translations.select_category }}</option>
              <option
                v-for="category in options.categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </div>
          <!-- Attachment -->
          <div class="ecwp-group form-group relative">
            <label for="attachment" class="ecwp-label form-label">{{
              translations.attached_file
            }}</label>
            <input
              type="file"
              id="attachment"
              ref="attachment"
              class="ecwp-input input input-bordered w-full peer"
            />
          </div>
          <!-- Note -->
          <div class="ecwp-group form-group mt-4 relative">
            <label for="note" class="ecwp-label form-label">{{
              translations.note
            }}</label>
            <textarea
              id="note"
              v-model="formData.note"
              class="ecwp-input textarea textarea-bordered w-full peer"
              rows="4"
              placeholder="Note"
            ></textarea>
          </div>
          <!-- Submit Button -->
          <div class="form-group mt-4 flex justify-end">
            <button
              type="submit"
              class="btn btn-primary rounded-full"
              :disabled="loadingBtn"
            >
              {{ translations.add }}
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
  data() {
    return {
      loadingBtn: false,
      formData: {
        amount: "",
        expense_date: "",
        client_id: "",
        category_id: "",
        attachment: null,
        note: "",
      },
      options: {
        clients: [],
        categories: [],
      },
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  methods: {
    async fetchOptions() {
      try {
        const responseClients = await fetch(
          "/wp-json/my-easy-compta/v1/expenses/clients",
          {
            headers: {
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        const response = await fetch(
          "/wp-json/my-easy-compta/v1/expenses/categories",
          {
            headers: {
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (response.ok) {
          const categories = await response.json();
          this.options.categories = categories;
          const clients = await responseClients.json();
          this.options.clients = clients;
        } else {
          console.error("Erreur lors de la récupération des catégories");
        }
      } catch (error) {
        console.error("Erreur lors de la récupération des options:", error);
      }
    },
    async submitForm() {
      this.loadingBtn = true;
      const formData = new FormData();
      formData.append("amount", this.formData.amount);
      formData.append("expense_date", this.formData.expense_date);
      formData.append("client_id", this.formData.client_id);
      formData.append("category_id", this.formData.category_id);
      if (this.$refs.attachment.files[0]) {
        formData.append("attachment", this.$refs.attachment.files[0]);
      }
      formData.append("note", this.formData.note);

      try {
        const response = await fetch("/wp-json/my-easy-compta/v1/expenses", {
          method: "POST",
          body: formData,
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        });

        if (response.ok) {
          const data = await response.json();
          this.loadingBtn = false;
          this.$emit("expenseAdded");
          this.resetForm();
          this.closeModal();
        } else {
          console.error("Erreur lors de l'ajout de la dépense");
          this.loadingBtn = false;
        }
      } catch (error) {
        console.error("Erreur lors de l'ajout de la dépense:", error);
        this.loadingBtn = false;
      }
    },
    closeModal() {
      const modal = document.getElementById("modal_expenses");
      modal.close();
    },
    resetForm() {
      this.formData = {
        amount: "",
        expense_date: "",
        client_id: "",
        category_id: "",
        attachment: null,
        note: "",
      };
      this.$refs.attachment.value = null;
    },
  },
  mounted() {
    this.fetchOptions();
  },
};
</script>
