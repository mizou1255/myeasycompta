<template>
  <div class="pt-2 pr-4">
    <GlobalSearch />
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
      modal-id="modal_quotes_remove"
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
        <div>
          <router-link :to="{ name: 'QuoteNew' }">
            <button class="btn btn-primary rounded-full">
              {{ translations.add }}
              <i class="fas fa-plus-circle"></i>
            </button>
          </router-link>
          <span
            v-if="settings.easy_compta_export_addon_active == 1"
            class="ms-2"
          >
            <a
              class="btn btn-outline btn-accent rounded-full hover:text-white"
              href="/wp-admin/admin.php?page=my-easy-compta-export#tab2"
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
                <div>{{ translations.quote_number }}</div>
                <input
                  v-model="filters.quote_number"
                  @input="fetchQuotesWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.client }}</div>
                <select
                  v-model="filters.client"
                  @change="fetchQuotesWithFilters()"
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
                <div>{{ translations.status }}</div>
                <select
                  v-model="filters.status"
                  @change="fetchQuotesWithFilters()"
                  class="ecwp-input input-xs input-bordered mt-2"
                >
                  <option value="">{{ translations.all_statuses }}</option>
                  <option value="draft">{{ translations.draft }}</option>
                  <option value="pending">{{ translations.pending }}</option>
                  <option value="approved">{{ translations.approved }}</option>
                  <option value="rejected">{{ translations.rejected }}</option>
                </select>
              </th>
              <th>
                <div>{{ translations.total }}</div>
                <input
                  v-model="filters.total_amount"
                  @input="fetchQuotesWithFilters()"
                  type="text"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.due_date }}</div>
                <input
                  v-model="filters.due_date"
                  @input="fetchQuotesWithFilters()"
                  type="date"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th>
                <div>{{ translations.created_at }}</div>
                <input
                  v-model="filters.created_at"
                  @input="fetchQuotesWithFilters()"
                  type="date"
                  class="ecwp-input input-xs input-bordered mt-2"
                />
              </th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody v-if="!loading">
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
        <div v-if="loading">
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
import GlobalSearch from "@/components/GlobalSearch.vue";
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
    GlobalSearch,
  },
  data() {
    return {
      quotes: [],
      filteredQuotes: [],
      filters: {
        quote_number: "",
        client: "",
        status: "",
        total_amount: "",
        due_date: "",
        created_at: "",
      },
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
      clients: [],
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
    this.fetchQuotesWithFilters();
    this.loadSettings();
    this.fetchClients();
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
    fetchQuotesWithFilters(page = 1) {
      this.loading = true;
      const { perPage, filters } = this;
      const query = new URLSearchParams({
        page,
        per_page: perPage,
        ...filters,
      }).toString();

      fetch(`/wp-json/my-easy-compta/v1/quotes?${query}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
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
          console.error("Error fetching quotes with filters:", error);
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
      this.fetchQuotesWithFilters(pageNumber);
    },
    perPageChanged() {
      this.fetchQuotesWithFilters();
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
            this.fetchQuotesWithFilters();
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
      modal_quotes_remove.showModal();
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
            this.fetchQuotesWithFilters();
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
