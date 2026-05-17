<template>
  <MainLayout :title="translations.clients || 'Clients'" :subtitle="translations.clients_subtitle || 'Manage your client base'">
    <template #actions>
        <button 
          @click="AddNew"
          class="bg-purple-600 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 flex items-center gap-2"
        >
          <Plus class="w-4 h-4" />
          {{ translations.add || 'Ajouter' }}
        </button>
        <button
          v-if="exportAddonActive"
          @click="openExport"
          class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-100 dark:border-slate-700 px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
        >
           <Download class="w-4 h-4" />
           {{ translations.export || 'Export' }}
        </button>
        <button
          @click="showMapModal = true"
          class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-100 dark:border-slate-700 px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
          :title="translations.clients_map || 'Carte des clients'"
        >
           <Map class="w-4 h-4" />
           {{ translations.map || 'Carte' }}
        </button>
    </template>

    <div class="space-y-6">
      <!-- Filters Top Bar -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800">
         <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative group">
                <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                <input 
                  v-model="filters.company_name"
                  @input="debouncedFetch"
                  type="text" 
                  class="kloxy-input !pr-12"
                  :placeholder="translations.filter || 'Nom entreprise...'"
                >
            </div>
            
            <input 
                v-model="filters.manager_name"
                @input="debouncedFetch"
                type="text"
                class="flex-1 min-w-[200px] kloxy-input"
                :placeholder="translations.manager_name || 'Manager name...'"
            >
            
            <input 
                v-model="filters.email"
                @input="debouncedFetch"
                type="text"
                class="flex-1 min-w-[200px] kloxy-input"
                :placeholder="translations.email || 'Email...'"
            >
            
            <button @click="resetFilters" class="p-4 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-purple-600 rounded-2xl transition-colors shadow-sm" :title="translations.reset || 'Reset'">
                <RefreshCcw class="w-5 h-5" />
            </button>

            <!-- Toggle archivés -->
            <button
               @click="toggleShowArchived"
               :class="showArchived ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30' : 'bg-slate-50 dark:bg-slate-800 text-slate-500'"
               class="flex items-center gap-2 px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all hover:scale-105 active:scale-95"
            >
               <Archive class="w-4 h-4" />
               {{ showArchived ? (translations.hide_archived || 'Masquer archivés') : (translations.show_archived || 'Afficher archivés') }}
            </button>
         </div>
      </div>

      <!-- Main List -->
      <div class="space-y-6">
        
        <!-- Stats / Controls -->
        <div class="flex items-center justify-between px-4">
            <div class="text-sm font-bold text-slate-500 dark:text-slate-400">
                <span v-if="!loading">{{ totalCount }} {{ translations.clients || 'clients' }}</span>
                <span v-else class="animate-pulse">{{ translations.loading || 'Loading...' }}</span>
            </div>
            
             <select
                  v-model="perPage"
                  @change="fetchClients(1)"
                  class="bg-transparent border-none text-slate-500 font-bold text-sm focus:ring-0 cursor-pointer"
                >
                  <option :value="10">{{ translations.per_page_10 || '10 per page' }}</option>
                  <option :value="20">{{ translations.per_page_20 || '20 per page' }}</option>
                  <option :value="50">{{ translations.per_page_50 || '50 per page' }}</option>
             </select>
        </div>

        <!-- Table Kloxy Style -->
        <div v-if="loading" class="space-y-4">
           <!-- Skeletons -->
           <div v-for="i in 5" :key="i" class="h-24 bg-white dark:bg-slate-900 rounded-[2rem] animate-pulse"></div>
        </div>

        <div v-else-if="clients.length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <Users class="w-8 h-8 text-slate-300" />
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ translations.no_clients_found || 'No client found' }}</h3>
            <p class="text-slate-500 mt-2 font-medium">{{ translations.client_help_text || 'Start by adding your first client.' }}</p>
             <button @click="AddNew" class="mt-6 text-purple-600 font-black text-sm uppercase tracking-widest hover:underline">
                {{ translations.new_client || 'Add a client' }}
             </button>
        </div>

        <table v-else class="w-full border-separate border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-4">
                    <th class="px-8 pb-2 text-left">{{ translations.company_name }}</th>
                    <th class="px-8 pb-2 text-left hidden md:table-cell">{{ translations.manager_name }}</th>
                    <th class="px-8 pb-2 text-left hidden lg:table-cell">{{ translations.contact }}</th>
                    <th class="px-8 pb-2 text-right">{{ translations.actions }}</th>
                </tr>
            </thead>
            <tbody>
                <tr 
                    v-for="client in clients" 
                    :key="client.id"
                    class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none transition-all duration-300 group"
                >
                    <td class="px-8 py-6 rounded-l-[2rem]">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center font-black text-slate-600 dark:text-slate-300 text-lg">
                                {{ client.company_name ? client.company_name.charAt(0).toUpperCase() : '?' }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-slate-900 dark:text-white text-base">{{ client.company_name }}</span>
                                    <span v-if="client.archived == 1" class="text-[9px] font-black uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-lg">Archivé</span>
                                </div>
                                <div class="text-xs font-bold text-slate-400 mt-1" v-if="client.city">
                                    {{ client.city }} <span v-if="client.country">• {{ client.country }}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6 hidden md:table-cell">
                         <span class="font-bold text-slate-600 dark:text-slate-400">{{ client.manager_name }}</span>
                    </td>
                    <td class="px-8 py-6 hidden lg:table-cell">
                        <div class="flex flex-col gap-1">
                            <div class="text-xs font-medium text-slate-500 flex items-center gap-2" v-if="client.email">
                                <Mail class="w-3 h-3 text-purple-500" /> {{ client.email }}
                            </div>
                            <div class="text-xs font-medium text-slate-500 flex items-center gap-2" v-if="client.phone">
                                <Phone class="w-3 h-3 text-purple-500" /> {{ client.phone }}
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6 rounded-r-[2rem] text-right">
                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="showClientDetails(client)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.view || 'Voir'">
                                <Eye class="w-5 h-5" />
                            </button>
                            <button v-if="client.archived != 1" @click="editClient(client)" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.edit || 'Editer'">
                                <Pencil class="w-5 h-5" />
                            </button>
                            <button
                               @click="archiveClientToggle(client)"
                               :class="client.archived == 1 ? 'text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20' : 'text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-slate-800'"
                               class="p-2 rounded-xl transition-colors"
                               :title="client.archived == 1 ? (translations.unarchive || 'Désarchiver') : (translations.archive || 'Archiver')"
                            >
                               <ArchiveRestore v-if="client.archived == 1" class="w-5 h-5" />
                               <Archive v-else class="w-5 h-5" />
                            </button>
                            <button v-if="client.archived != 1" @click="confirmDeleteClient(client)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-xl transition-colors" :title="translations.delete || 'Supprimer'">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex justify-center mt-8">
            <nav class="flex gap-2 bg-white dark:bg-slate-900 p-2 rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800">
                <button 
                  @click="goToPage(currentPage - 1)" 
                  :disabled="currentPage === 1"
                  class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:hover:bg-transparent text-slate-500 transition-colors"
                >
                    <ChevronLeft class="w-5 h-5" />
                </button>
                
                <button 
                   v-for="page in paginationButtons" 
                   :key="page"
                   @click="goToPage(page)"
                   :class="page === currentPage 
                     ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' 
                     : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'"
                   class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-sm transition-all"
                   :disabled="page === '...'"
                >
                    {{ page }}
                </button>

                <button 
                  @click="goToPage(currentPage + 1)" 
                  :disabled="currentPage === totalPages"
                  class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:hover:bg-transparent text-slate-500 transition-colors"
                >
                    <ChevronRight class="w-5 h-5" />
                </button>
            </nav>
        </div>

      </div>
    </div>

    <!-- Modals (Legacy Wrappers or New Components?) -->
    <!-- I will use the legacy components within the new layout for now, assuming they are complex -->
    <!-- Ideally we'd refactor these too but sticking to 1 file for now, while ensuring they work -->
    <AddClientModal :show-modal="showAddModal" @close="showAddModal = false" @clientAdded="fetchClients(1)" />
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
      modal-id="modal_client_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="deleteClient(selectedClient)"
      @cancel="showRemoveModal = false"
    />

    <ClientMap
      :show-modal="showMapModal"
      :clients="allClients"
      @close="showMapModal = false"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import MainLayout from '@/components/layout/MainLayout.vue';
import { Plus, Download, Search, Mail, Phone, Eye, Pencil, Trash2, Users, ChevronLeft, ChevronRight, RefreshCcw, Archive, ArchiveRestore, Map } from 'lucide-vue-next';
import { generatePaginationButtons, showToast } from "@/utils/helpers"; // Keep generic helpers

// Legacy Component Imports
import AddClientModal from "@/components/clients/Add.vue";
import ClientDetailsModal from "@/components/clients/View.vue";
import ClientEditModal from "@/components/clients/Edit.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import ClientMap from "@/components/clients/ClientMap.vue";
import { fetchSettings } from "@/api/api"; // Ensure this path is correct

const route = useRoute();

// State
const clients = ref([]);
const loading = ref(true);
const totalCount = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(10);
const loadingModal = ref(false);
const showClientDetailsModal = ref(false);
const editClientModal = ref(false);
const showAddModal = ref(false);
const showRemoveModal = ref(false);
const selectedClient = ref(null);
const paginationButtons = ref([]);

// Refs (if needed, but using props preferred)

const showArchived = ref(false);
const showMapModal = ref(false);
const allClients = ref([]);

const filters = reactive({
    company_name: '',
    manager_name: '',
    email: '',
    phone: ''
});

// Computed
const translations = computed(() => {
    return window.myEasyComptaAdmin?.easyComptaTranslations || {};
});

const exportAddonActive = computed(() => {
   // Logic from original
    if (window.myEasyComptaAdmin && window.myEasyComptaAdmin.exportAddonActive) {
        return true;
    }
    // Checking settings if available (need to load settings still?)
    return false; 
});

// Methods
const debouncedFetch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetchClients(1);
        }, 500);
    }
})();

const fetchClients = (page = 1) => {
    loading.value = true;
    
    // Construct query
    const query = new URLSearchParams({
        page,
        per_page: perPage.value,
        show_archived: showArchived.value ? 1 : 0,
        company_name: filters.company_name,
        manager_name: filters.manager_name,
        email: filters.email,
        phone: filters.phone,
    }).toString();

    const nonce = window.myEasyComptaAdmin?.nonce || '';
    
    fetch(`/wp-json/my-easy-compta/v1/clients?${query}`, {
        headers: { "X-WP-Nonce": nonce }
    })
    .then(res => res.json())
    .then(data => {
        clients.value = data.clients || [];
        totalCount.value = data.total_count || 0;
        totalPages.value = data.total_pages || 1;
        currentPage.value = data.page || page;
        
        paginationButtons.value = generatePaginationButtons(currentPage.value, totalPages.value);
    })
    .catch(err => {
    })
    .finally(() => {
        loading.value = false;
    });
};

const goToPage = (page) => {
    if (page === '...' || page < 1 || page > totalPages.value) return;
    fetchClients(page);
};

const resetFilters = () => {
    filters.company_name = '';
    filters.manager_name = '';
    filters.email = '';
    filters.phone = '';
    fetchClients(1);
};

const toggleShowArchived = () => {
    showArchived.value = !showArchived.value;
    fetchClients(1);
};

const archiveClientToggle = async (client) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/clients/${client.id}/archive`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
        });
        const data = await res.json();
        if (data.success) {
            client.archived = data.archived ? 1 : 0;
            if (!showArchived.value && client.archived) {
                fetchClients(currentPage.value);
            }
        }
    } catch (e) {}
};

// Actions
const AddNew = () => {
    showAddModal.value = true;
};

const openExport = () => {
    window.location.href='/wp-admin/admin.php?page=my-easy-compta-export#tab1';
};

const showClientDetails = (client) => {
    loadingModal.value = true;
    showClientDetailsModal.value = true;
    // Fetch details logic reused
    fetchClientDetails(client.id);
};

const editClient = (client) => {
    loadingModal.value = true;
    editClientModal.value = true;
    fetchClientDetails(client.id);
};

const confirmDeleteClient = (client) => {
    selectedClient.value = client;
    showRemoveModal.value = true;
};

const deleteClient = (client) => {
    if (!client) return;
    loading.value = true;
    fetch(`/wp-json/my-easy-compta/v1/clients/${client.id}`, {
        method: "DELETE",
        headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
    })
    .then(res => {
         if(!res.ok) throw new Error("Network error");
         return res.json();
    })
    .then(data => {
        showRemoveModal.value = false;
        if(data.success) {
            fetchClients(currentPage.value);
            // Show toast (simple alert for now or custom toast system needed)
        } else {
        }
    })
    .catch(err => {
        showRemoveModal.value = false;
    })
    .finally(() => loading.value = false);
};

const fetchClientDetails = (id) => {
    fetch(`/wp-json/my-easy-compta/v1/clients/details/${id}`, {
        headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
    })
    .then(res => res.json())
    .then(data => {
        selectedClient.value = data;
    })
    .finally(() => loadingModal.value = false);
};

const handleOpenClientModal = (event) => {
      const clientId = event.detail?.clientId;
      if (clientId) {
        showClientDetails({ id: clientId });
      }
};

watch(showMapModal, (val) => {
    if (val && allClients.value.length === 0) {
        fetch(`/wp-json/my-easy-compta/v1/clients?per_page=500&page=1`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin?.nonce || '' }
        })
        .then(r => r.json())
        .then(d => { allClients.value = d.clients || []; })
        .catch(() => {});
    }
});

onMounted(() => {
    fetchClients();

    // Ouvrir la modale d'ajout si on arrive depuis le dashboard
    if (route.query.add === '1') {
        showAddModal.value = true;
    }

    // URL params check
    const urlParams = new URLSearchParams(window.location.search);
    const openClientId = urlParams.get('openClient');
    if (openClientId) {
       showClientDetails({ id: parseInt(openClientId) });
    }
    
    window.addEventListener('ecwp:open-client-modal', handleOpenClientModal);
});

onUnmounted(() => {
    window.removeEventListener('ecwp:open-client-modal', handleOpenClientModal);
});

</script>
