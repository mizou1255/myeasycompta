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
    <confirm-modal
      :show-modal="showConfirmModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      @confirm="this.duplicateQuote(selectedQuote)"
      @cancel="showConfirmModal = false"
    />
    <remove-modal
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="this.deleteQuote(selectedQuote)"
      @cancel="showRemoveModal = false"
    />

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.quotes }}</h2>
        <router-link :to="{ name: 'QuoteNew' }">
          <button class="btn btn-primary rounded-full">
            {{ translations.add }}
            <i class="fas fa-plus-circle"></i>
          </button>
        </router-link>
      </div>
      <div class="divider mt-2"></div>
      <div class="overflow-x-auto">
        <table v-if="!loading" class="table w-full">
          <thead>
            <tr>
              <th>{{ translations.quote_number }}</th>
              <th>{{ translations.client }}</th>
              <th>{{ translations.status }}</th>
              <th>{{ translations.total }}</th>
              <th>{{ translations.due_date }}</th>
              <th>{{ translations.created_at }}</th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="quote in quotes" :key="quote.id">
              <td>{{ quote.quote_number }}</td>
              <td>{{ quote.client_name }}</td>
              <td>
                <span
                  v-if="quote.status == 'draft'"
                  class="badge badge-warning badge-outline badge-sm"
                  >{{ translations.draft }}</span
                >
                <span
                  v-if="quote.status == 'pending'"
                  class="badge badge-secondary badge-outline badge-sm"
                  >{{ translations.pending }}</span
                >
                <span
                  v-if="quote.status == 'approved'"
                  class="badge badge-success badge-outline badge-sm"
                  >{{ translations.approved }}</span
                >
                <span
                  v-if="quote.status == 'rejected'"
                  class="badge badge-error badge-outline badge-sm"
                  >{{ translations.rejected }}</span
                >
              </td>
              <td>
                <div v-if="!loadingPrice">
                  <div v-if="settings.vat_active == 1">
                    <span
                      v-if="
                        default_currency_symbol == quote.client_currency ||
                        quote.client_currency == null
                      "
                      >{{
                        formatAmount(
                          quote.total_amount,
                          default_currency_symbol
                        )
                      }}</span
                    >
                    <span v-else>{{
                      formatAmount(quote.total_amount, quote.client_currency)
                    }}</span>
                  </div>
                  <div v-else>
                    <span
                      v-if="
                        default_currency_symbol == quote.client_currency ||
                        quote.client_currency == null
                      "
                      >{{
                        formatAmount(
                          quote.total_amount,
                          default_currency_symbol
                        )
                      }}</span
                    >
                    <span v-else>{{
                      formatAmount(quote.total_amount, quote.client_currency)
                    }}</span>
                  </div>
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>
              <td>
                <div v-if="!loadingPrice">{{ quote.due_date }}</div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>
              <td>
                <div v-if="!loadingPrice">{{ quote.created }}</div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>

              <td class="flex justify-end">
                <span class="lg:tooltip" :data-tip="translations.view">
                  <router-link
                    :to="{ name: 'QuoteViewDetail', params: { id: quote.id } }"
                  >
                    <button class="btn btn-circle mx-1">
                      <i class="far fa-eye"></i>
                    </button> </router-link
                ></span>
                <span class="lg:tooltip" :data-tip="translations.edit">
                  <router-link
                    :to="{
                      name: 'QuoteEdit',
                      params: { id: quote.id },
                    }"
                  >
                    <button class="btn btn-circle mx-1">
                      <i class="fas fa-pencil-alt"></i>
                    </button> </router-link
                ></span>

                <span class="lg:tooltip" :data-tip="translations.duplicate">
                  <button
                    @click="confirmDuplicateQuote(quote.id)"
                    class="btn btn-circle text-purple-600 hover:text-purple-900 mx-1"
                  >
                    <i class="far fa-copy"></i></button
                ></span>
                <span class="lg:tooltip" :data-tip="translations.delete">
                  <button
                    @click="confirmDeleteQuote(quote.id)"
                    class="btn btn-circle text-red-500 hover:text-red-700 mx-1"
                  >
                    <i class="far fa-trash-alt"></i></button
                ></span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else>
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
      </div>
      <!-- Pagination -->
      <div class="join ecwp_pagination mt-6 pt-4">
        <button
          v-for="pageNumber in paginationButtons"
          :key="pageNumber"
          class="join-item btn"
          :class="{
            'btn-disabled': pageNumber === '...' || pageNumber === currentPage,
          }"
          @click="goToPage(pageNumber)"
        >
          {{ pageNumber }}
        </button>
      </div>
    </Card>
  </div>
</template>
  
  <script>
import Card from "@/components/Card.vue";
import QuoteEditModal from "@/components/quotes/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
import { fetchSettings } from "@/api/api";
import {
  calculateVAT,
  calculateWithoutVAT,
  generatePaginationButtons,
  formatAmount,
  showToast,
} from "@/utils/helpers";

export default {
  name: "QuotesList",
  components: {
    Card,
    QuoteEditModal,
    ConfirmModal,
    RemoveModal,
  },
  data() {
    return {
      quotes: [],
      showQuoteDetailsModal: false,
      editQuoteModal: false,
      selectedQuote: null,
      currentPage: 1,
      totalCount: 0,
      totalPages: 1,
      paginationButtons: [],
      loading: true,
      loadingPrice: true,
      loadingModal: false,
      skeletonRows: 5,
      perPage: 10,
      perPageOptions: [5, 10, 20, 50],
      client_currency: "",
      default_vat: "",
      default_currency: "",
      default_currency_symbol: "",
      settings: {},
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
    };
  },
  created() {
    this.fetchQuotes();
    this.loadSettings();
  },
  methods: {
    fetchQuotes(page = 1) {
      this.loading = true;
      const { perPage } = this;
      fetch(
        `/wp-json/my-easy-compta/v1/quotes?page=${page}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.quotes = data.quotes;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching quotes:", error);
        })
        .finally(() => {
          this.loading = false;
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
    calculateVAT(amount) {
      return calculateVAT(amount, this.default_vat.rate);
    },
    calculateWithoutVAT(amount) {
      return calculateWithoutVAT(amount);
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
      this.fetchQuotes(pageNumber);
    },
    perPageChanged() {
      this.fetchQuotes();
    },
    formatAmount(amount, currency) {
      return formatAmount(amount, currency, this.settings.currency_position);
    },
    confirmDuplicateQuote(quote_id) {
      this.selectedQuote = quote_id;
      modal_confirm.showModal();
      this.showRemoveModal = true;
    },
    duplicateQuote(quote_id) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/quotes/duplicate/${quote_id}`, {
        method: "POST",
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
            this.fetchQuotes();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error duplicate quote:", data.statusText);
          }
        })
        .catch((error) => {
          console.log(error.message);
          this.showToast(error.message, "alert-error");
        });
    },
    confirmDeleteQuote(quote) {
      this.selectedQuote = quote;
      modal_remove.showModal();
      this.showRemoveModal = true;
    },
    deleteQuote(quote_id) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/quotes/delete/${quote_id}`, {
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
            this.fetchQuotes();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error deleting quote:", data.statusText);
          }
        })
        .catch((error) => {
          console.log(error.message);
          this.showToast(error.message, "alert-error");
        });
    },
    showToast(message, type) {
      showToast(this.toast, message, type);
    },
  },
  computed: {
    skeletonItems() {
      return Array.from({ length: 5 }, (_, index) => index);
    },
    totalPages() {
      return Math.ceil(this.totalCount / this.perPage);
    },
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
};
</script>
  