<template>
  <MainLayout :title="translations.edit_quote" subtitle="Modifier les informations du devis">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <!-- Back Button -->
    <div class="mb-6">
       <button @click="cancelAction" class="flex items-center text-slate-400 hover:text-purple-600 font-bold text-sm transition-colors cursor-pointer">
          <ArrowLeft class="w-4 h-4 mr-2" /> {{ translations.back || 'Retour' }}
       </button>
    </div>

    <div class="max-w-5xl mx-auto bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative">
       <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-[2.5rem]">
           <div class="w-10 h-10 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
       </div>

       <form @submit.prevent="submitQuote" class="space-y-8">
           
           <!-- Section 1 -->
           <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">{{ translations.quote_number }}</label>
                 <div class="relative">
                   <input type="text" v-model="quote.quote_number" disabled class="w-full !bg-slate-50 dark:!bg-slate-950 !border !border-slate-100 dark:!border-slate-800 rounded-2xl px-6 py-4 font-bold text-sm text-slate-500 cursor-not-allowed outline-none" />
                   <Lock class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2" />
                 </div>
              </div>
              
              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                    {{ translations.due_date }}
                 </label>
                  <div class="relative group">
                    <VueDatePicker
                        v-model="quote.due_date"
                        :enable-time-picker="false"
                        auto-apply
                        :format="formattedDate"
                        :min-date="new Date()"
                        locale="fr"
                        input-class-name="kloxy-input !pl-12"
                        :class="{'!ring-2 !ring-rose-500/50': !quote.due_date && showError}"
                    >
                        <template #input-icon>
                            <Calendar class="w-4 h-4 ml-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                        </template>
                    </VueDatePicker>
                  </div>
                 <p v-if="!quote.due_date && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required || 'Requis' }}</p>
              </div>

              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                    {{ translations.provisional_date }}
                 </label>
                  <div class="relative group">
                    <VueDatePicker
                        v-model="quote.provisional_start_date"
                        :enable-time-picker="false"
                        auto-apply
                        :format="formattedDate"
                        :min-date="new Date()"
                        locale="fr"
                        input-class-name="kloxy-input !pl-12"
                        :class="{'!ring-2 !ring-rose-500/50': !quote.provisional_start_date && showError}"
                    >
                        <template #input-icon>
                            <Calendar class="w-4 h-4 ml-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                        </template>
                    </VueDatePicker>
                  </div>
                 <p v-if="!quote.provisional_start_date && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required || 'Requis' }}</p>
              </div>
           </div>
           
           <!-- Section 2 -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     {{ translations.company_name }} <span class="text-rose-500">*</span>
                  </label>
                  <div class="flex-1 relative group kloxy-select-wrapper">
                         <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 z-10 pointer-events-none group-focus-within:text-purple-500 transition-colors" />
                         <model-select
                            v-model="quote.client_id"
                            :options="clientOptions"
                            :placeholder="translations.select_client || 'Sélectionner un client'"
                            class="kloxy-select"
                         />
                    </div>
                  <p v-if="!quote.client_id && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required }}</p>
               </div>

               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     {{ translations.status }}
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
                  <p v-if="!quote.status && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required }}</p>
               </div>
           </div>

           <!-- Notes internes -->
           <div class="space-y-3">
              <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1 flex items-center gap-2">
                 <StickyNote class="w-4 h-4" />
                 {{ translations.internal_notes || 'Notes internes' }}
                 <span class="font-medium normal-case tracking-normal text-slate-300">({{ translations.not_printed || 'non imprimées' }})</span>
              </label>
              <div class="relative">
                 <textarea
                    v-model="quote.internal_notes"
                    @blur="saveNotes"
                    rows="3"
                    class="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-medium text-sm text-slate-700 dark:text-white resize-none focus:ring-4 focus:ring-purple-500/10 transition-all outline-none"
                    :placeholder="translations.internal_notes_placeholder || 'Mémo interne, rappel, instructions spéciales...'"
                 ></textarea>
                 <span v-if="notesSaved" class="absolute bottom-3 right-4 text-[10px] font-black text-emerald-500 uppercase tracking-widest">✓ Enregistré</span>
              </div>
           </div>

           <!-- Actions -->
           <div class="flex items-center justify-end gap-4 pt-8 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="cancelAction" class="bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">
                  {{ translations.cancel }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Save v-else class="w-4 h-4" />
                   {{ translations.save }}
               </button>
           </div>
       </form>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import MainLayout from '@/components/layout/MainLayout.vue';
import { ModelSelect } from "vue-search-select";
import VueDatePicker from "@vuepic/vue-datepicker";
import { ArrowLeft, Lock, ChevronDown, Save, X, CheckCircle2, AlertCircle, Calendar, User, StickyNote } from 'lucide-vue-next';
import axios from "axios";
import 'vue-search-select/dist/VueSearchSelect.css';
import '@vuepic/vue-datepicker/dist/main.css';

const router = useRouter();
const route = useRoute();

// State
const clientOptions = ref([]);
const clients = ref([]);
const quote = reactive({
  quote_number: "",
  due_date: "",
  provisional_start_date: "",
  client_id: "",
  status: "",
  internal_notes: "",
});
const showError = ref(false);
const loading = ref(false);
const loadingBtn = ref(false);
const notesSaved = ref(false);
const toast = reactive({ visible: false, message: "", type: "success" });

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

const fetchClients = async () => {
    loading.value = true;
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/list-clients?per_page=999`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        // Handle varying response structure
        const data = res.data.clients ? res.data.clients : (Array.isArray(res.data) ? res.data : []);
        clients.value = data;
        clientOptions.value = clients.value.map(c => ({
            value: String(c.id),
            text: `${c.company_name} - ${c.email} (${c.currency_symbol || '€'})`
        }));
    } catch (e) {} finally { loading.value = false; }
};

const fetchQuoteDetails = async () => {
    loading.value = true;
    try {
        const id = route.params.id;
        const res = await axios.get(`/wp-json/my-easy-compta/v1/quotes/${id}`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        const quoteData = res.data.quote || res.data;
        Object.assign(quote, quoteData);
        
        // Handle Dates conversion robustly
        const dates = ['due_date', 'provisional_start_date'];
        dates.forEach(field => {
            const dateStr = quoteData[`${field}_raw`] || quoteData[field];
            if (dateStr && typeof dateStr === 'string') {
                 const dateObj = new Date(dateStr);
                 if (!isNaN(dateObj.getTime())) {
                     quote[field] = dateObj;
                 } else {
                     const parts = dateStr.split("-");
                     if(parts.length === 3) {
                          if (parts[0].length === 4) quote[field] = new Date(parts[0], parts[1] - 1, parts[2]);
                          else quote[field] = new Date(parts[2], parts[1] - 1, parts[0]);
                     }
                 }
            }
        });
        
        // Convert client_id to string for ModelSelect
        if (quote.client_id) quote.client_id = String(quote.client_id);
    } catch(e) { 
        showToast("Erreur lors du chargement", "error");
    } finally { loading.value = false; }
};

const submitQuote = async () => {
  showError.value = true;
  if (!quote.due_date || !quote.provisional_start_date || !quote.client_id || !quote.status) {
    showToast("Veuillez remplir tous les champs obligatoires.", "error");
    return;
  }

  loadingBtn.value = true;
  const id = route.params.id;

  try {
     const payload = { ...quote };
     if (typeof payload.client_id === 'object' && payload.client_id !== null) {
         payload.client_id = payload.client_id.value;
     }

    const response = await axios.put(`/wp-json/my-easy-compta/v1/quotes/${id}`, payload, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });

    if (response.data.success) {
      showToast(response.data.message, "success");
      setTimeout(() => router.push({ name: "QuoteViewDetail", params: { id } }), 1500);
    } else {
      showToast(response.data.message, "error");
    }
  } catch (error) {
    showToast("Erreur lors de la modification", "error");
  } finally {
    loadingBtn.value = false;
  }
};

const saveNotes = async () => {
    const id = route.params.id;
    try {
        await axios.post(`/wp-json/my-easy-compta/v1/quotes/${id}/notes`, { internal_notes: quote.internal_notes }, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        notesSaved.value = true;
        setTimeout(() => notesSaved.value = false, 2000);
    } catch (e) {}
};

const cancelAction = () => router.push("/quotes");

onMounted(async () => {
   await fetchClients();
   await fetchQuoteDetails();
});
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
