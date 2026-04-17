<template>
  <MainLayout :title="translations.new_quote" subtitle="Créer un nouveau devis">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed top-4 right-4 z-50 animate-in fade-in slide-in-from-top-4 duration-300">
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
    <div v-if="showTemplatePanel && templates.length > 0" class="max-w-5xl mx-auto mb-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900 rounded-2xl p-5">
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

    <!-- Restore Banner -->
    <div v-if="showRestoreBanner" class="max-w-5xl mx-auto mb-4 flex items-center justify-between gap-4 px-5 py-3 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 text-amber-700 dark:text-amber-300">
       <span class="text-sm font-semibold flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          Un brouillon non soumis a été trouvé. Voulez-vous le restaurer ?
       </span>
       <div class="flex items-center gap-2 flex-shrink-0">
          <button @click="restoreDraft" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-amber-500 text-white hover:bg-amber-600 transition-colors cursor-pointer">Restaurer</button>
          <button @click="clearDraft" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer">Ignorer</button>
       </div>
    </div>

    <div class="max-w-5xl mx-auto bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative">
        <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-[2.5rem]">
            <div class="flex flex-col items-center gap-4">
                <div class="w-12 h-12 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm font-bold text-slate-500 animate-pulse">Chargement...</p>
            </div>
        </div>

        <form @submit.prevent="submitQuote" class="space-y-8">
            <!-- Section 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-3">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">{{ translations.quote_number }}</label>
                    <div class="relative">
                        <input type="text" v-model="quote.number" disabled class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl px-6 py-4 font-bold text-sm text-slate-500 cursor-not-allowed" />
                        <Lock class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2" />
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                        {{ translations.due_date }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative group">
                        <VueDatePicker
                            v-model="quote.due_date"
                            :enable-time-picker="false"
                            auto-apply
                            :format="formattedDate"
                            locale="fr"
                            input-class-name="kloxy-input !pl-12"
                            :class="{'!ring-2 !ring-rose-500/50': !quote.due_date && showError}"
                        >
                            <template #input-icon>
                                <Calendar class="w-4 h-4 ml-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                            </template>
                        </VueDatePicker>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                        {{ translations.provisional_date }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative group">
                        <VueDatePicker
                            v-model="quote.provisional_start_date"
                            :enable-time-picker="false"
                            auto-apply
                            :format="formattedDate"
                            locale="fr"
                            input-class-name="kloxy-input !pl-12"
                            :class="{'!ring-2 !ring-rose-500/50': !quote.provisional_start_date && showError}"
                        >
                            <template #input-icon>
                                <Calendar class="w-4 h-4 ml-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                            </template>
                        </VueDatePicker>
                    </div>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                        {{ translations.company_name }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="flex-1 relative group kloxy-select-wrapper">
                            <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 z-10 pointer-events-none group-focus-within:text-purple-500 transition-colors" />
                            <model-select
                                v-model="quote.client_id"
                                :options="clientOptions"
                                label="text"
                                track-by="value"
                                :placeholder="translations.select"
                                class="kloxy-select"
                            />
                        </div>
                        <button type="button" @click="AddNew" class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 hover:bg-purple-600 hover:text-white rounded-xl flex items-center justify-center transition-all shrink-0">
                            <Plus class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                        {{ translations.status }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select v-model="quote.status" class="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer">
                            <option value="draft">{{ translations.draft }}</option>
                            <option value="pending">{{ translations.pending }}</option>
                            <option value="approved">{{ translations.approved }}</option>
                            <option value="rejected">{{ translations.rejected }}</option>
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
import { ArrowLeft, Lock, Plus, ChevronDown, Check, X, CheckCircle2, AlertCircle, Calendar, User, LayoutTemplate, ChevronRight } from 'lucide-vue-next';
import axios from "axios";
import 'vue-search-select/dist/VueSearchSelect.css';
import '@vuepic/vue-datepicker/dist/main.css';

const router = useRouter();

// State
const clientOptions = ref([]);
const clients = ref([]);
const quote = reactive({
  number: "",
  due_date: "",
  provisional_start_date: "",
  client_id: "",
  status: "draft",
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
  quote.client_id = { value: newClient.id, text: newClient.company_name };
};

const fetchClients = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/wp-json/my-easy-compta/v1/list-clients?per_page=999`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
    if (response.data.success || response.data) {
       // list-clients returns {clients: []} or just []? Previous file said response.json() -> data.clients || data
       // Legacy code said: data.clients || data || []
       const data = response.data.clients ? response.data.clients : (Array.isArray(response.data) ? response.data : []);
       clients.value = data;
       clientOptions.value = clients.value.map(c => ({ value: c.id, text: `${c.company_name} - ${c.email} (${c.currency_symbol})` }));
    }
  } catch (e) {} finally { loading.value = false; }
};

const fetchSettings = async () => {
  try {
    const response = await axios.get(`/wp-json/my-easy-compta/v1/settings/get`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
    if (response.data) {
      settings.value = response.data;
      const lastId = String(settings.value.last_quote_id || 0).padStart(4, "0");
      quote.number = `${settings.value.quote_prefix}_${lastId}`;
    }
  } catch (e) {}
};

const submitQuote = async () => {
  showError.value = true;
  if (!quote.due_date || !quote.provisional_start_date || !quote.client_id || !quote.status) {
    showToast(translations.value.fill_required_fields || "Veuillez remplir tous les champs obligatoires", "error");
    return;
  }

  loadingBtn.value = true;
  try {
    const payload = { ...quote };
    // Unwrap model-select
    if(payload.client_id && typeof payload.client_id === 'object') payload.client_id = payload.client_id.value;
    
    // axios uses JSON by default
    const response = await axios.post(`/wp-json/my-easy-compta/v1/quotes`, payload, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });

    if (response.data.success) {
      clearDraft();
      showToast(response.data.message || "Devis créé avec succès", "success");
      setTimeout(() => router.push({ name: "QuoteViewDetail", params: { id: response.data.id } }), 1500);
    } else {
      showToast(response.data.message || translations.value.error_creating_invoice, "error");
    }
  } catch (error) {
    showToast("Erreur lors de la création", "error");
  } finally {
    loadingBtn.value = false;
  }
};

const cancelAction = () => router.push("/quotes");

const fetchTemplates = async () => {
  try {
    const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/templates`, {
      headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
    });
    const data = await res.json();
    templates.value = data.templates || [];
  } catch (e) {}
};

const createFromTemplate = async (templateId) => {
  creatingFromTemplate.value = true;
  try {
    const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/duplicate/${templateId}`, {
      method: "POST",
      headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
    });
    const data = await res.json();
    if (!res.ok || data.code) {
      showToast(data.message || "Erreur lors de la création depuis le modèle", "error");
    } else {
      clearDraft();
      showToast(translations.value.quote_created_from_template || "Devis créé depuis le modèle", "success");
      setTimeout(() => router.push({ name: "QuoteEdit", params: { id: data.new_quote_id } }), 1000);
    }
  } catch (e) {} finally { creatingFromTemplate.value = false; }
};

// ── Auto-save draft ───────────────────────────────────────────────────
const DRAFT_KEY = 'ecwp_draft_quote';
const draftSavedAt = ref(null);
const showRestoreBanner = ref(false);

const saveDraft = () => {
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify({
      due_date:               quote.due_date instanceof Date ? quote.due_date.toISOString() : quote.due_date,
      provisional_start_date: quote.provisional_start_date instanceof Date ? quote.provisional_start_date.toISOString() : quote.provisional_start_date,
      client_id:              quote.client_id,
      status:                 quote.status,
      _savedAt:               Date.now(),
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
    if (saved.due_date)               quote.due_date               = new Date(saved.due_date);
    if (saved.provisional_start_date) quote.provisional_start_date = new Date(saved.provisional_start_date);
    if (saved.client_id)              quote.client_id              = saved.client_id;
    if (saved.status)                 quote.status                 = saved.status;
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
