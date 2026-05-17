<template>
  <MainLayout :title="translations.new_invoice" subtitle="Créer une nouvelle facture">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <!-- Back Button -->
    <div class="mb-6 flex items-center justify-between">
       <button @click="cancelAction" class="flex items-center text-slate-400 hover:text-purple-600 font-bold text-sm transition-colors cursor-pointer">
          <ArrowLeft class="w-4 h-4 mr-2" /> {{ translations.back || 'Retour' }}
       </button>
       <div class="flex items-center gap-4">
         <button v-if="templates.length > 0" @click="showTemplatePanel = !showTemplatePanel"
           class="flex items-center gap-2 text-xs font-black text-indigo-600 hover:text-indigo-700 transition-colors"
         >
           <LayoutTemplate class="w-4 h-4" />
           {{ translations.create_from_template || 'Créer depuis un modèle' }}
           <ChevronRight class="w-3.5 h-3.5 transition-transform" :class="showTemplatePanel ? 'rotate-90' : ''" />
         </button>
         <span v-if="draftSavedAt" class="text-[11px] font-bold text-emerald-500 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse inline-block"></span>
            Brouillon sauvegardé à {{ draftSavedAt }}
         </span>
       </div>
    </div>

    <!-- Template picker -->
    <div v-if="showTemplatePanel && templates.length > 0" class="max-w-4xl mx-auto mb-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900 rounded-2xl p-5">
      <p class="text-xs font-black uppercase tracking-widest text-indigo-400 mb-3">{{ translations.available_templates || 'Modèles disponibles' }}</p>
      <div class="flex flex-wrap gap-3">
        <button
          v-for="tpl in templates"
          :key="tpl.id"
          @click="createFromTemplate(tpl.id)"
          :disabled="creatingFromTemplate"
          class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-800 rounded-xl text-sm font-bold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:border-indigo-400 transition-all disabled:opacity-50 shadow-sm"
        >
          <LayoutTemplate class="w-4 h-4 shrink-0" />
          {{ tpl.name }}
        </button>
      </div>
    </div>

    <!-- Restore banner -->
    <div v-if="showRestoreBanner" class="max-w-4xl mx-auto mb-4 flex items-center justify-between gap-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-2xl px-6 py-4">
      <span class="text-sm font-bold text-amber-700 dark:text-amber-300">Un brouillon non soumis a été trouvé. Voulez-vous le restaurer ?</span>
      <div class="flex gap-2 flex-shrink-0">
        <button @click="restoreDraft" class="text-xs font-black bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl transition-colors">Restaurer</button>
        <button @click="clearDraft" class="text-xs font-black bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 transition-colors hover:bg-slate-50">Ignorer</button>
      </div>
    </div>

    <div class="max-w-4xl mx-auto bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative">
       <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-[2.5rem]">
           <div class="w-10 h-10 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
       </div>

       <form @submit.prevent="submitInvoice" class="space-y-8">
           
           <!-- Section 1 -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">{{ translations.invoice_number }}</label>
                 <div class="relative">
                   <input type="text" v-model="invoice.number" disabled class="w-full !bg-slate-50 dark:!bg-slate-950 !border !border-slate-100 dark:!border-slate-800 rounded-2xl px-6 py-4 font-bold text-sm text-slate-500 cursor-not-allowed outline-none" />
                   <span class="text-[10px] font-bold text-slate-400 mt-2 block ml-2 flex items-center gap-1"><Info class="w-3 h-3" /> Généré automatiquement</span>
                 </div>
              </div>
              
              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                    {{ translations.due_date }} <span class="text-rose-500">*</span>
                 </label>
                  <div class="relative group">
                    <VueDatePicker
                        v-model="invoice.due_date"
                        :enable-time-picker="false"
                        auto-apply
                        :format="formattedDate"
                        :min-date="new Date()"
                        locale="fr"
                        input-class-name="kloxy-input !pl-12"
                        :class="{'!ring-2 !ring-rose-500/50': !invoice.due_date && showError}"
                    >
                        <template #input-icon>
                            <Calendar class="w-4 h-4 ml-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                        </template>
                    </VueDatePicker>
                  </div>
                 <p v-if="!invoice.due_date && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required || 'Requis' }}</p>
              </div>
           </div>
           
           <!-- Section 2 -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     {{ translations.company_name }} <span class="text-rose-500">*</span>
                  </label>
                  <div class="flex gap-3">
                      <div class="flex-1 relative group kloxy-select-wrapper">
                         <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 z-10 pointer-events-none group-focus-within:text-purple-500 transition-colors" />
                         <model-select
                            v-model="invoice.client_id"
                            :options="clientOptions"
                            :placeholder="translations.select_client || 'Sélectionner un client'"
                            class="kloxy-select"
                         />
                      </div>
                     <button type="button" @click="AddNew" class="w-12 h-[52px] bg-purple-100 dark:bg-slate-800 text-purple-600 hover:bg-purple-600 hover:text-white rounded-2xl flex items-center justify-center transition-all">
                        <Plus class="w-5 h-5" />
                     </button>
                  </div>
                  <p v-if="!invoice.client_id && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required }}</p>
               </div>

               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     {{ translations.status }} <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative">
                     <select v-model="invoice.status" class="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer">
                        <option value="draft">{{ translations.draft }}</option>
                        <option value="unpaid">{{ translations.unpaid }}</option>
                        <option value="paid">{{ translations.paid }}</option>
                     </select>
                     <ChevronDown class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
                  <p v-if="!invoice.status && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required }}</p>
               </div>
           </div>

           <!-- Section 3 : Facturation électronique -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     Type de transaction <span class="text-rose-500">*</span>
                  </label>
                  <p class="text-[11px] text-slate-400 font-medium ml-1 -mt-1">Obligatoire pour la facturation électronique</p>
                  <div class="relative">
                     <select v-model="invoice.transaction_type" class="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer">
                        <option value="B2B">B2B — Entre professionnels</option>
                        <option value="B2C">B2C — Vers un particulier</option>
                        <option value="B2G">B2G — Vers une administration</option>
                     </select>
                     <ChevronDown class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
               </div>
           </div>

           <!-- Actions -->
           <div class="flex items-center justify-end gap-4 pt-8 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="cancelAction" class="bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">
                  {{ translations.cancel }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Check v-else class="w-4 h-4" />
                   {{ translations.submit }}
               </button>
           </div>
       </form>
    </div>

    <!-- Modal -->
    <AddClientModal 
      :showModal="showAddClientModal"
      @close="showAddClientModal = false"
      @clientAdded="onClientAdded" 
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import MainLayout from '@/components/layout/MainLayout.vue';
import { ModelSelect } from "vue-search-select";
import VueDatePicker from "@vuepic/vue-datepicker";
import AddClientModal from "@/components/clients/Add.vue";
import { ArrowLeft, Info, Plus, ChevronDown, Check, X, CheckCircle2, AlertCircle, Calendar, User, LayoutTemplate, ChevronRight } from 'lucide-vue-next';
import axios from "axios";
import 'vue-search-select/dist/VueSearchSelect.css';
import '@vuepic/vue-datepicker/dist/main.css';

const router = useRouter();

// State
const clientOptions = ref([]);
const clients = ref([]);
const invoice = reactive({
  number: "",
  due_date: "",
  client_id: "",
  status: "draft",
  transaction_type: "B2B",
});
const showError = ref(false);
const loading = ref(false);
const loadingBtn = ref(false);
const settings = ref({});
const showAddClientModal = ref(false);
const toast = reactive({ visible: false, message: "", type: "success" });
const templates = ref([]);
const showTemplatePanel = ref(false);
const creatingFromTemplate = ref(false);

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const formattedDate = (date) => {
  if (!date) return "";
  const day = date.getDate().toString().padStart(2, "0");
  const month = (date.getMonth() + 1).toString().padStart(2, "0");
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
};

// Methods
const showToast = (message, type = "success") => {
  toast.message = message;
  toast.type = type;
  toast.visible = true;
  setTimeout(() => toast.visible = false, 3000);
};

const AddNew = () => showAddClientModal.value = true;

const onClientAdded = (newClient) => {
  showAddClientModal.value = false;
  fetchClients();
  invoice.client_id = { value: newClient.id, text: newClient.company_name };
};

const fetchClients = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/wp-json/my-easy-compta/v1/list-clients?per_page=999`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
    const data = response.data.clients || response.data || [];
    clients.value = data;
    clientOptions.value = clients.value.map(c => ({ 
      value: String(c.id), 
      text: `${c.company_name} - ${c.email} (${c.currency_symbol || '€'})` 
    }));
  } catch (e) {} finally { loading.value = false; }
};

const fetchSettings = async () => {
  try {
    const response = await axios.get(`/wp-json/my-easy-compta/v1/settings/get`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
    if (response.data) {
      settings.value = response.data;
      invoice.number = settings.value.next_invoice_number || '';
    }
  } catch (e) {}
};

const submitInvoice = async () => {
  showError.value = true;
  if (!invoice.due_date || !invoice.client_id || !invoice.status) {
    showToast(translations.value.fill_required_fields || "Veuillez remplir tous les champs obligatoires", "error");
    return;
  }

  loadingBtn.value = true;
  try {
    const rawClientId = invoice.client_id;
    const payload = {
      ...invoice,
      client_id: (typeof rawClientId === 'object' && rawClientId !== null)
        ? rawClientId.value
        : rawClientId,
      due_date: invoice.due_date.toISOString().split('T')[0],
    };

    const response = await axios.post(`/wp-json/my-easy-compta/v1/invoices`, payload, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });

    if (response.data.success) {
      clearDraft();
      showToast(translations.value.invoice_created || "Facture créée avec succès", "success");
      setTimeout(() => router.push({ name: "Invoices" }), 1500);
    } else {
      showToast(response.data.message || translations.value.error_creating_invoice, "error");
    }
  } catch (error) {
    showToast(translations.value.error_creating_invoice, "error");
  } finally {
    loadingBtn.value = false;
  }
};

const cancelAction = () => router.go(-1);

const fetchTemplates = async () => {
  try {
    const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/templates`, {
      headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
    });
    const data = await res.json();
    templates.value = data.templates || [];
  } catch (e) {}
};

const createFromTemplate = async (templateId) => {
  creatingFromTemplate.value = true;
  try {
    const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/${templateId}/duplicate`, {
      method: "POST",
      headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
    });
    const data = await res.json();
    if (!res.ok || data.code) {
      showToast(data.message || "Erreur lors de la création depuis le modèle", "error");
    } else {
      clearDraft();
      showToast(translations.value.invoice_created_from_template || "Facture créée depuis le modèle", "success");
      setTimeout(() => router.push({ name: "InvoiceEdit", params: { id: data.new_invoice_id } }), 1000);
    }
  } catch (e) {} finally { creatingFromTemplate.value = false; }
};

// ── Auto-save draft ───────────────────────────────────────────────────
const DRAFT_KEY = 'ecwp_draft_invoice';
const draftSavedAt = ref(null);
const showRestoreBanner = ref(false);

const saveDraft = () => {
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify({
      due_date:         invoice.due_date instanceof Date ? invoice.due_date.toISOString() : invoice.due_date,
      client_id:        invoice.client_id,
      status:           invoice.status,
      transaction_type: invoice.transaction_type,
      _savedAt:         Date.now(),
    }));
    draftSavedAt.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  } catch(e) { /* localStorage full */ }
};

const clearDraft = () => {
  localStorage.removeItem(DRAFT_KEY);
  draftSavedAt.value = null;
  showRestoreBanner.value = false;
};

const restoreDraft = () => {
  try {
    const saved = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null');
    if (!saved) return;
    if (saved.due_date)         invoice.due_date         = new Date(saved.due_date);
    if (saved.client_id) {
      // Normalize: always store as {value, text} object for ModelSelect
      invoice.client_id = (typeof saved.client_id === 'object' && saved.client_id !== null)
        ? saved.client_id
        : { value: String(saved.client_id), text: '' };
    }
    if (saved.status)           invoice.status           = saved.status;
    if (saved.transaction_type) invoice.transaction_type = saved.transaction_type;
  } catch(e) { /* invalid JSON */ }
  clearDraft();
  saveDraft();
  showRestoreBanner.value = false;
};

let draftTimer = null;

onMounted(() => {
  fetchClients();
  fetchSettings();
  fetchTemplates();

  // Offer to restore existing draft (max 7 days old)
  try {
    const saved = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null');
    if (saved && saved._savedAt && Date.now() - saved._savedAt < 7 * 86400_000) {
      showRestoreBanner.value = true;
    } else if (saved) {
      localStorage.removeItem(DRAFT_KEY);
    }
  } catch(e) { localStorage.removeItem(DRAFT_KEY); }

  draftTimer = setInterval(saveDraft, 30_000);
});

onUnmounted(() => clearInterval(draftTimer));
</script>

<style>
/* Custom overrides for vue-search-select to match Kloxy */
.kloxy-select-wrapper .ui.selection.dropdown {
    background: #f8fafc !important; /* Slate-50 */
    border: none !important;
    border-radius: 1rem !important;
    padding: 1rem 1.5rem 1rem 3.5rem !important; /* Added left padding for icon */
    font-weight: 700 !important;
    font-size: 0.875rem !important;
    min-height: 52px !important;
    display: flex !important;
    align-items: center !important;
    box-shadow: none !important;
    width: 100% !important;
    transition: all 0.2s ease !important;
}

.dark .kloxy-select-wrapper .ui.selection.dropdown {
    background: #020617 !important; /* Slate-950 */
    color: white !important;
}

.kloxy-select-wrapper .ui.selection.dropdown:focus, 
.kloxy-select-wrapper .ui.selection.dropdown.active {
    box-shadow: 0 0 0 4px rgba(147, 51, 234, 0.1) !important;
}

.kloxy-select-wrapper .ui.selection.dropdown .text {
    color: #0f172a !important; /* Slate-900 */
}

.dark .kloxy-select-wrapper .ui.selection.dropdown .text {
    color: white !important;
}

.kloxy-select-wrapper .ui.selection.dropdown .menu {
    border: none !important;
    border-radius: 1rem !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    margin-top: 0.5rem !important;
    overflow: hidden !important;
    background: white !important;
    z-index: 1000 !important;
}

.dark .kloxy-select-wrapper .ui.selection.dropdown .menu {
    background: #0f172a !important; /* Slate-900 */
}

.kloxy-select-wrapper .ui.selection.dropdown .menu .item {
    padding: 1rem 1.5rem !important;
    transition: background 0.2s ease !important;
}

.kloxy-select-wrapper .ui.selection.dropdown .menu .item:hover {
    background: #f8fafc !important;
}

.dark .kloxy-select-wrapper .ui.selection.dropdown .menu .item:hover {
    background: #1e293b !important;
}

.kloxy-select-wrapper .ui.selection.dropdown > .dropdown.icon {
    right: 1.5rem !important;
}
</style>
