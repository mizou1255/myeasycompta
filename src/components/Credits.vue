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

    <remove-modal
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="this.deleteCredit(selectedCredit)"
      @cancel="showRemoveModal = false"
    />

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.credits }}</h2>
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
        <table v-if="!loading" class="table w-full">
          <thead>
            <tr>
              <th>{{ translations.credit_number }}</th>
              <th>{{ translations.invoice_number }}</th>
              <th>{{ translations.client }}</th>
              <th>{{ translations.payment_date }}</th>
              <th>{{ translations.created_at }}</th>
              <th>{{ translations.amount }}</th>
              <th class="flex justify-end">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="credit in credits" :key="credit.id">
              <td>{{ credit.credit_number }}</td>
              <td>{{ credit.invoice_number }}</td>
              <td>{{ credit.client_name }}</td>
              <td>{{ credit.due_date }}</td>
              <td>{{ credit.created_at }}</td>
              <td>
                <div v-if="!loadingPrice">
                  <span>{{
                    formatAmount(credit.total_amount, default_currency_symbol)
                  }}</span>
                </div>
                <div v-else>
                  <span class="loading loading-bars loading-sm"></span>
                </div>
              </td>

              <td class="flex justify-end">
                <span class="lg:tooltip" :data-tip="translations.delete">
                  <button
                    @click="confirmDeleteCredit(credit.id)"
                    class="btn btn-circle text-red-500 hover:text-red-700 mx-1"
                  >
                    <i class="far fa-trash-alt"></i></button
                ></span>
                <span class="lg:tooltip" :data-tip="translations.export">
                  <button
                    @click="exportToPDF(credit.credit_id)"
                    class="btn btn-circle mx-1 text-green-700"
                    :disabled="loadingPdfId === credit.credit_id"
                  >
                    <i
                      class="far fa-file-pdf"
                      aria-hidden="true"
                      v-if="loadingPdfId !== credit.credit_id"
                    ></i>
                    <span
                      v-if="loadingPdfId === credit.credit_id"
                      class="loading loading-spinner loading-sm"
                    ></span></button
                ></span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else>
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
import RemoveModal from "@/components/RemoveAlert.vue";
import { fetchSettings } from "@/api/api";
import {
  generatePaginationButtons,
  formatAmount,
  showToast,
} from "@/utils/helpers";

export default {
  name: "Credits",
  components: {
    Card,
    RemoveModal,
  },
  data() {
    return {
      credits: [],
      selectedCredit: null,
      currentPage: 1,
      totalPages: 1,
      paginationButtons: [],
      loading: true,
      loadingPrice: true,
      loadingPdfId: null,
      loadingModal: false,
      showRemoveModal: false,
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
    };
  },
  created() {
    this.fetchCredits();
    this.loadSettings();
  },
  methods: {
    fetchCredits(page = 1) {
      this.loading = true;
      const { perPage } = this;
      fetch(
        `/wp-json/my-easy-compta/v1/credits?page=${page}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.credits = data.credits;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = data.page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching credits:", error);
        })
        .finally(() => {
          this.loading = false;
        });
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
      this.fetchCredits(pageNumber);
    },
    perPageChanged() {
      this.fetchCredits();
    },
    formatAmount(amount, currency) {
      return formatAmount(amount, currency, this.settings.currency_position);
    },
    showToast(message, type) {
      showToast(this.toast, message, type);
    },
    confirmDeleteCredit(credit) {
      this.selectedCredit = credit;
      modal_remove.showModal();
      this.showRemoveModal = true;
    },
    deleteCredit(creditId) {
      this.loading = true;
      fetch(`/wp-json/my-easy-compta/v1/credits/${creditId}`, {
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
            this.fetchCredits();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error deleting credit:", data.statusText);
          }
        })
        .catch((error) => {
          console.error("Error deleting credit:", error);
        });
    },
    exportToPDF(invoiceId) {
      this.loadingPdfId = invoiceId;
      fetch(`/wp-json/my-easy-compta/v1/credits/pdf/${invoiceId}`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            this.loadingPdfId = null;
            throw new Error("Network response was not ok");
          }
          this.loadingPdfId = null;
          return response.blob();
        })
        .then((blob) => {
          const url = URL.createObjectURL(blob);
          this.loadingPdfId = null;
          window.open(url);
        })
        .catch((error) => {
          this.loadingPdfId = null;
          console.error("There was a problem with the fetch operation:", error);
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
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
};
</script>
