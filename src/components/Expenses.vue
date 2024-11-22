<template>
  <div class="pt-2 pr-4">
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>

    <AddExpenseModal @expenseAdded="fetchExpenses" />

    <expense-edit-modal
      :loading="loading"
      :show-modal="editExpenseModal"
      modal-id="modal_expense_edit"
      :modal-title="translations.edit_expense"
      :expense="selectedExpense"
      :categories="categoriesExpenses"
      :clients="clientOptions"
      @expenseEdited="fetchExpenses"
    />

    <remove-modal
      modal-id="modal_expense_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="this.deleteExpense(selectedExpense)"
      @cancel="showRemoveModal = false"
    />

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.expenses }}</h2>
        <div>
          <button class="btn btn-primary rounded-full" @click="AddNew">
            {{ translations.add }} <i class="fas fa-plus-circle"></i>
          </button>
          <span
            v-if="settings.easy_compta_export_addon_active == 1"
            class="ms-2"
          >
            <a
              class="btn btn-outline btn-accent rounded-full hover:text-white"
              href="/wp-admin/admin.php?page=my-easy-compta-export#tab5"
            >
              {{ translations.export }}
              <i class="fas fa-file-export"></i>
            </a>
          </span>
          <span
            v-else
            class="tooltip tooltip-left tooltip-warning ms-2"
            :data-tip="translations.active_export_addon"
          >
            <button class="btn btn-outline btn-accent rounded-full" disabled>
              {{ translations.export }}
              <i class="fas fa-file-export"></i>
            </button>
          </span>
        </div>
      </div>
      <div class="divider mt-2"></div>

      <div class="flex items-center mb-4">
        <label for="perPageSelect" class="mr-2">{{
          translations.display_per_page
        }}</label>
        <select id="perPageSelect" v-model="perPage" @change="perPageChanged">
          <option
            v-for="option in perPageOptions"
            :key="option"
            :value="option"
          >
            {{ option }}
          </option>
        </select>
      </div>
      <div class="overflow-x-auto">
        <table class="table w-full">
          <thead>
            <tr>
              <th>
                <div>{{ translations.expense_date }}</div>
                <input
                  v-model="filters.expense_date"
                  @input="fetchExpensesWithFilters()"
                  type="date"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.amount }}</div>
                <input
                  v-model="filters.total_amount"
                  @input="fetchExpensesWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.client }}</div>
                <select
                  v-model="filters.client"
                  @change="fetchExpensesWithFilters()"
                  class="ecwp-input input-xs input-bordered mt-2"
                >
                  <option value="">{{ translations.all }}</option>
                  <option
                    v-for="client in clients"
                    :key="client.id"
                    :value="client.company_name"
                  >
                    {{ client.company_name }}
                  </option>
                </select>
              </th>
              <th>
                <div>{{ translations.category }}</div>
                <select
                  v-model="filters.category"
                  @change="fetchExpensesWithFilters()"
                  class="ecwp-input input-xs input-bordered mt-2"
                >
                  <option value="">{{ translations.all }}</option>
                  <option
                    v-for="category in categories_expenses"
                    :key="category.id"
                    :value="category.name"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </th>
              <th class="align-top">{{ translations.attachment }}</th>
              <th class="align-top">{{ translations.note }}</th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody v-if="!loading">
            <tr v-for="expense in expenses" :key="expense.id">
              <td>{{ expense.expense_date }}</td>
              <td>
                <div v-if="!loadingPrice">
                  <span>{{
                    formatAmount(expense.amount, default_currency_symbol)
                  }}</span>
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>
              <td>{{ expense.company_name }}</td>
              <td>{{ expense.name }}</td>
              <td>
                <div v-if="expense.attachment_url" class="avatar">
                  <div
                    v-if="isImage(expense.type)"
                    class="w-16 mask mask-squircle"
                  >
                    <a :href="expense.attachment_url" target="_blank">
                      <img :src="expense.attachment_url"
                    /></a>
                  </div>
                  <div v-else class="w-16 mask mask-squircle">
                    <a :href="expense.attachment_url" target="_blank">
                      <img :src="defaultImage" />
                    </a>
                  </div>
                </div>
              </td>
              <td>{{ expense.notes }}</td>
              <td class="flex justify-end">
                <span class="lg:tooltip" :data-tip="translations.edit">
                  <button
                    @click="editExpense(expense.id)"
                    class="btn btn-circle mx-1"
                  >
                    <i class="fas fa-pencil-alt"></i></button
                ></span>

                <span class="lg:tooltip" :data-tip="translations.delete">
                  <button
                    @click="confirmDeleteExpense(expense.id)"
                    class="btn btn-circle text-red-500 hover:text-red-700 mx-1"
                  >
                    <i class="far fa-trash-alt"></i></button
                ></span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading">
          <!-- Skeleton loader -->
          <div
            v-for="n in skeletonRows"
            :key="n"
            class="flex flex-col gap-4 w-full"
          >
            <div class="flex gap-4 items-center">
              <div class="skeleton w-16 h-16 rounded-full shrink-0"></div>
              <div class="flex flex-col gap-4 w-full">
                <div class="skeleton h-4 w-full"></div>
                <div class="skeleton h-4 w-full"></div>
              </div>
            </div>
            <div class="divider my-1"></div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="join ecwp_pagination mt-6 pt-4">
          <button
            v-for="pageNumber in paginationButtons"
            :key="pageNumber"
            class="join-item btn"
            :class="{
              'btn-disabled':
                pageNumber === '...' || pageNumber === currentPage,
            }"
            @click="goToPage(pageNumber)"
          >
            {{ pageNumber }}
          </button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script>
import Card from "@/components/Card.vue";
import AddExpenseModal from "@/components/expenses/Add.vue";
import ExpenseEditModal from "@/components/expenses/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import { fetchSettings } from "@/api/api";
import {
  generatePaginationButtons,
  formatAmount,
  showToast,
} from "@/utils/helpers";

export default {
  name: "Expenses",
  components: {
    Card,
    AddExpenseModal,
    ExpenseEditModal,
    RemoveModal,
  },
  data() {
    return {
      expenses: [],
      filteredExpenses: [],
      filters: {
        client: "",
        expense_date: "",
        total_amount: "",
        category: "",
      },
      categories_expenses: [],
      categoriesExpenses: [],
      listClients: [],
      clientOptions: [],
      currentPage: 1,
      totalPages: 1,
      paginationButtons: [],
      loading: true,
      loadingPrice: true,
      skeletonRows: 5,
      perPage: 10,
      perPageOptions: [5, 10, 20, 50],
      settings: [],
      default_currency_symbol: "",
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      editExpenseModal: false,
      showRemoveModal: false,
      selectedExpense: null,
    };
  },
  created() {
    this.fetchExpensesWithFilters();
    this.fetchClients();
    this.fetchCategoriesExpenses();
    this.loadSettings();
  },
  methods: {
    AddNew() {
      modal_expenses.showModal();
    },
    isImage(type) {
      return ["jpg", "jpeg", "png", "gif", "webp"].includes(type);
    },
    fetchExpenses(page = 1) {
      this.loading = true;
      const { perPage } = this;
      fetch(
        `/wp-json/my-easy-compta/v1/expenses?page=${page}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.expenses = data.expenses;
          this.totalPages = data.total_pages;
          this.currentPage = data.page;
          this.totalCount = data.total_count;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching expenses:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchExpensesWithFilters(page = 1) {
      this.loading = true;
      const { perPage, filters } = this;
      const query = new URLSearchParams({
        page,
        per_page: perPage,
        ...filters,
      }).toString();

      fetch(`/wp-json/my-easy-compta/v1/expenses?${query}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.expenses = data.expenses;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching expenses with filters:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchClients() {
      fetch(`/wp-json/my-easy-compta/v1/clients`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.clients = data.clients;
        })
        .catch((error) => {
          console.error("Error fetching clients:", error);
        });
    },
    fetchCategoriesExpenses() {
      fetch(`/wp-json/my-easy-compta/v1/expenses/categories`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.categories_expenses = data;
        })
        .catch((error) => {
          console.error("Error fetching categories expenses:", error);
        });
    },
    async loadSettings() {
      try {
        this.loadingPrice = true;
        const { settings, currencySymbol, vatData } = await fetchSettings();
        this.settings = settings;
        this.default_currency_symbol = currencySymbol;
        this.default_vat = vatData;
        this.loadingPrice = false;
      } catch (error) {
        this.showToast(error.message, "alert-error");
        this.loadingPrice = false;
      }
    },
    generatePaginationButtons() {
      this.paginationButtons = generatePaginationButtons(
        this.currentPage,
        this.totalPages
      );
    },
    goToPage(pageNumber) {
      if (pageNumber === "...") {
        return;
      }
      this.fetchExpensesWithFilters(pageNumber);
    },
    perPageChanged() {
      this.fetchExpensesWithFilters();
    },
    formatAmount(amount, currency) {
      return formatAmount(amount, currency, this.settings.currency_position);
    },
    showToast(message, type) {
      showToast(this.toast, message, type);
    },
    editExpense(expense) {
      this.loadingModal = true;
      this.editExpenseModal = true;
      modal_expense_edit.showModal();
      this.fetchExpenseDetails(expense);
    },
    fetchExpenseDetails(expenseId) {
      fetch(`/wp-json/my-easy-compta/v1/expenses/details/${expenseId}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.selectedExpense = data;
          this.categoriesExpenses = data.categories_expenses;
          this.listClients = data.list_clients;
          this.clientOptions = this.listClients.map((client) => ({
            value: client.id,
            text: `${client.company_name}`,
          }));
          this.loadingModal = false;
        })
        .catch((error) => {
          console.error("Error fetching expense details:", error);
          this.loadingModal = false;
        });
    },
    confirmDeleteExpense(expense) {
      this.selectedExpense = expense;
      modal_expense_remove.showModal();
      this.showRemoveModal = true;
    },
    deleteExpense(expenseId) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/expenses/${expenseId}`, {
        method: "DELETE",
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            this.fetchExpenses();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error deleting expense:", data.statusText);
          }
        })
        .catch((error) => {
          console.error("Error deleting expense:", error);
        });

      this.showRemoveModal = false;
    },
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
    defaultImage() {
      return window.myEasyComptaAdmin.pluginUrl + "/assets/img/file.svg";
    },
  },
};
</script>
