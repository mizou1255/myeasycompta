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

    <client-details-modal
      :loading="loadingModal"
      :show-modal="showClientDetailsModal"
      modal-id="modal_client_details"
      :modal-title="translations.client_details"
      :client="selectedClient"
      @close="showClientDetailsModal = false"
    />

    <client-edit-modal
      :loading="loadingModal"
      :show-modal="editClientModal"
      modal-id="modal_client_edit"
      :modal-title="translations.edit_client"
      :client="selectedClient"
      @close="editClientModal = false"
      @clientEdited="fetchClients"
    />

    <remove-modal
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="deleteClient(selectedClient)"
      @cancel="showRemoveModal = false"
    />

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.clients }}</h2>
        <button class="btn btn-primary rounded-full" @click="AddNew">
          {{ translations.add }} <i class="fas fa-plus-circle"></i>
        </button>
      </div>
      <div class="divider mt-2"></div>

      <AddClientModal @clientAdded="fetchClients" />

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
              <th>{{ translations.company_name }}</th>
              <th>{{ translations.manager_name }}</th>
              <th>{{ translations.email }}</th>
              <th>{{ translations.phone }}</th>
              <th class="flex justify-center">{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="client in clients" :key="client.id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="avatar">
                    <div class="mask mask-squircle w-12 h-12">
                      <svg
                        version="1.1"
                        id="Layer_1"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        x="0px"
                        y="0px"
                        viewBox="0 0 122.9 122.9"
                        style="enable-background: new 0 0 122.9 122.9"
                        xml:space="preserve"
                      >
                        <g>
                          <path
                            d="M61.4,0c17,0,32.3,6.9,43.4,18c11.1,11.1,18,26.5,18,43.4c0,17-6.9,32.3-18,43.4c-11.1,11.1-26.5,18-43.4,18 s-32.3-6.9-43.4-18C6.9,93.8,0,78.4,0,61.4c0-17,6.9-32.3,18-43.4C29.1,6.9,44.5,0,61.4,0L61.4,0z M41.3,54.3c-1.1,0-2,0.3-2.5,0.7 c-0.3,0.2-0.6,0.5-0.7,0.8c-0.2,0.4-0.3,0.8-0.2,1.4c0,1.5,0.8,3.5,2.4,5.8l0,0l0,0l5,8c2,3.2,4.1,6.5,6.8,8.9 c2.5,2.3,5.6,3.9,9.6,3.9c4.4,0,7.6-1.6,10.2-4.1c2.7-2.5,4.9-6,7-9.5l5.7-9.3c1.1-2.4,1.4-4,1.2-5c-0.1-0.6-0.8-0.8-1.8-0.9 c-0.2,0-0.5,0-0.7,0c-0.3,0-0.5,0-0.8,0c-0.2,0-0.3,0-0.4,0c-0.5,0-1,0-1.6-0.1l1.9-8.6c-14.4,2.3-25.2-8.4-40.4-2.1L43,54.4 C42.4,54.4,41.8,54.4,41.3,54.3L41.3,54.3L41.3,54.3L41.3,54.3z M18.8,95.7c7.1-2.5,19.6-3.8,25.4-7.7c1-1.3,2.1-2.9,3.1-4.3 c0.6-0.9,1.1-1.7,1.6-2.3c0.1-0.1,0.2-0.2,0.3-0.3c-2.4-2.5-4.4-5.5-6.3-8.5l-5-8C36,61.8,35,59.3,35,57.3c0-1,0.1-1.9,0.5-2.6 c0.4-0.8,1-1.5,1.7-2c0.4-0.2,0.8-0.5,1.2-0.6c-0.3-4.3-0.4-9.8-0.2-14.4c0.1-1.1,0.3-2.2,0.6-3.3c1.3-4.6,4.5-8.3,8.5-10.8 c1.4-0.9,2.9-1.6,4.6-2.2c2.9-1.1,1.5-5.5,4.7-5.6c7.5-0.2,19.8,6.2,24.6,11.4c2.8,3,4.6,7,4.9,12.3l-0.3,13.1l0,0 c1.4,0.4,2.3,1.3,2.7,2.7c0.4,1.6,0,3.8-1.4,6.9l0,0c0,0.1-0.1,0.1-0.1,0.2l-5.7,9.4c-2.2,3.6-4.5,7.3-7.5,10.1L73.7,82l0,0 c0.4,0.5,0.8,1.1,1.2,1.7c0.8,1.1,1.6,2.4,2.5,3.6c5.3,4.5,19.3,5.9,26.7,8.6c7.6-9.4,12.1-21.4,12.1-34.4c0-15.1-6.1-28.8-16-38.7 c-9.9-9.9-23.6-16-38.7-16s-28.8,6.1-38.7,16c-9.9,9.9-16,23.6-16,38.7C6.7,74.4,11.2,86.3,18.8,95.7L18.8,95.7z M77,90.5 c-1.4-1.6-2.8-3.7-4.1-5.5c-0.4-0.5-0.7-1.1-1.1-1.5c-2.7,2-6,3.3-10.3,3.3c-4.5,0-8-1.6-10.9-4.1c0,0,0,0.1-0.1,0.1 c-0.5,0.7-1,1.4-1.6,2.3c-1.1,1.6-2.3,3.3-3.4,4.8C45.6,100,71.1,106,77,90.5L77,90.5z"
                          />
                        </g>
                      </svg>
                    </div>
                  </div>
                  <div>
                    <div class="font-bold">{{ client.company_name }}</div>
                    <div class="text-sm opacity-50">
                      <span class="badge badge-ghost badge-sm"
                        >{{ client.city }} - {{ client.country }}</span
                      >
                    </div>
                  </div>
                </div>
              </td>
              <td>
                {{ client.manager_name }}
              </td>
              <td>{{ client.email }}</td>
              <td>{{ client.phone }}</td>
              <td class="flex justify-end">
                <span class="lg:tooltip" :data-tip="translations.view">
                  <button
                    class="btn btn-circle mx-1"
                    @click="showClientDetails(client)"
                  >
                    <i class="far fa-eye"></i></button
                ></span>

                <span class="lg:tooltip" :data-tip="translations.edit">
                  <button
                    class="btn btn-circle mx-1"
                    @click="editClient(client)"
                  >
                    <i class="fas fa-pencil-alt"></i></button
                ></span>

                <span class="lg:tooltip" :data-tip="translations.delete">
                  <button
                    @click="confirmDeleteClient(client)"
                    class="btn btn-circle text-red-500 hover:text-red-700 mx-1"
                  >
                    <i class="far fa-trash-alt"></i></button
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
import AddClientModal from "@/components/clients/Add.vue";
import ClientDetailsModal from "@/components/clients/View.vue";
import ClientEditModal from "@/components/clients/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import { generatePaginationButtons, showToast } from "@/utils/helpers";

export default {
  name: "Clients",
  components: {
    Card,
    AddClientModal,
    ClientDetailsModal,
    ClientEditModal,
    RemoveModal,
  },
  data() {
    return {
      clients: [],
      showClientDetailsModal: false,
      editClientModal: false,
      showRemoveModal: false,
      selectedClient: null,
      currentPage: 1,
      totalPages: 1,
      paginationButtons: [],
      loading: true,
      loadingModal: false,
      skeletonRows: 5,
      perPage: 10,
      perPageOptions: [5, 10, 20, 50],
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
    };
  },
  created() {
    this.fetchClients();
  },
  methods: {
    AddNew() {
      modal_clients.showModal();
    },
    fetchClients(page = 1) {
      this.loading = true;
      const { perPage } = this;
      fetch(
        `/wp-json/my-easy-compta/v1/clients?page=${page}&per_page=${perPage}`,
        {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          this.clients = data.clients;
          this.totalCount = data.total_count;
          this.totalPages = data.total_pages;
          this.currentPage = data.page;
          this.perPage = perPage;
          this.generatePaginationButtons();
        })
        .catch((error) => {
          console.error("Error fetching clients:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchClientDetails(clientId) {
      fetch(`/wp-json/my-easy-compta/v1/clients/details/${clientId}`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.selectedClient = data;
          this.loadingModal = false;
        })
        .catch((error) => {
          console.error("Error fetching client details:", error);
          this.loadingModal = false;
        });
    },
    showClientDetails(client) {
      this.loadingModal = true;
      this.showClientDetailsModal = true;
      modal_client_details.showModal();
      this.fetchClientDetails(client.id);
    },

    editClient(client) {
      this.loadingModal = true;
      this.editClientModal = true;
      modal_client_edit.showModal();
      this.fetchClientDetails(client.id);
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
      this.fetchClients(pageNumber);
    },
    perPageChanged() {
      this.fetchClients();
    },
    confirmDeleteClient(client) {
      this.selectedClient = client;
      modal_remove.showModal();
      this.showRemoveModal = true;
    },

    deleteClient(client) {
      this.loading = true;
      const clientId = client.id;
      fetch(`/wp-json/my-easy-compta/v1/clients/${clientId}`, {
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
            this.fetchClients();
            this.showToast(data.message, "alert-success");
          } else {
            this.showToast(data.message, "alert-error");
            console.error("Error deleting client:", data.statusText);
          }
        })
        .catch((error) => {
          console.log(error.message);
          const errorMessage =
            error && error.message ? error.message : "Error deleting client";
          if (
            errorMessage ===
            "This client cannot be deleted because it has associated data."
          ) {
            this.showToast(errorMessage, "alert-error");
          } else {
            console.error("Error deleting client:", error);
          }
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
