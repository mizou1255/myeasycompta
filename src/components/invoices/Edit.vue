<template>
  <MainLayout :title="translations.edit_invoice" subtitle="Modifier la facture">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed top-4 right-4 z-50 animate-in fade-in slide-in-from-top-4 duration-300">
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

    <div class="max-w-4xl mx-auto bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative">
       <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-[2.5rem]">
           <div class="w-10 h-10 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
       </div>

        <!-- Locked Warning -->
       <div v-if="isLocked" class="mb-8 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-3xl p-6 flex items-center gap-4 text-rose-600 dark:text-rose-400">
          <Lock class="w-6 h-6" />
          <p class="font-bold text-sm">Cette facture est validée et ne peut plus être modifiée légalement.</p>
       </div>

       <form @submit.prevent="submitInvoice" class="space-y-8">
           
           <!-- Section 1 -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">{{ translations.invoice_number }}</label>
                 <div class="relative">
                   <input type="text" v-model="invoice.invoice_number" disabled class="w-full !bg-slate-50 dark:!bg-slate-950 !border !border-slate-100 dark:!border-slate-800 rounded-2xl px-6 py-4 font-bold text-sm text-slate-500 cursor-not-allowed outline-none" />
                   <Lock class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2" />
                 </div>
              </div>
              
              <div class="space-y-3">
                 <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                    {{ translations.due_date }}
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
                        :disabled="isLocked"
                    >
                        <template #input-icon>
                            <Calendar class="w-4 h-4 ml-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                        </template>
                    </VueDatePicker>
                    <Lock v-if="isLocked" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 z-10" />
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
                  <div class="flex-1 relative group kloxy-select-wrapper">
                         <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 z-10 pointer-events-none group-focus-within:text-purple-500 transition-colors" />
                         <model-select
                            v-model="invoice.client_id"
                            :options="clientOptions"
                            :placeholder="translations.select_client || 'Sélectionner un client'"
                            class="kloxy-select"
                            :is-disabled="isLocked"
                         />
                         <Lock v-if="isLocked" class="w-4 h-4 text-slate-400 absolute right-12 top-1/2 -translate-y-1/2 z-10" />
                    </div>
                  <p v-if="!invoice.client_id && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required }}</p>
               </div>

               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     {{ translations.status }}
                  </label>
                  <div class="relative">
                     <select v-model="invoice.status" :disabled="isLocked" class="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none disabled:cursor-not-allowed">
                        <option value="draft">{{ translations.draft }}</option>
                        <option value="unpaid">{{ translations.unpaid }}</option>
                        <option value="paid">{{ translations.paid }}</option>
                     </select>
                     <Lock v-if="isLocked" class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
                  <p v-if="!invoice.status && showError" class="text-xs font-bold text-rose-500 ml-2 animate-pulse">{{ translations.field_required }}</p>
               </div>
           </div>

           <!-- Section 3 : Type de transaction -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
               <div class="space-y-3">
                  <label class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">
                     Type de transaction
                  </label>
                  <p class="text-[11px] text-slate-400 font-medium ml-1 -mt-1">Obligatoire pour la facturation électronique</p>
                  <div class="relative">
                     <select v-model="invoice.transaction_type" :disabled="isLocked" class="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none disabled:cursor-not-allowed cursor-pointer">
                        <option value="B2B">B2B — Entre professionnels</option>
                        <option value="B2C">B2C — Vers un particulier</option>
                        <option value="B2G">B2G — Vers une administration</option>
                     </select>
                     <Lock v-if="isLocked" class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none" />
                     <ChevronDown v-else class="w-4 h-4 text-slate-400 absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
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
                    v-model="invoice.internal_notes"
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
               <button v-if="!isLocked" type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
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
const invoice = reactive({
  invoice_number: "",
  due_date: "",
  client_id: "",
  status: "",
  transaction_type: "B2B",
  fiscal_status: "",
  internal_notes: "",
});
const showError = ref(false);
const loading = ref(false);
const loadingBtn = ref(false);
const notesSaved = ref(false);
const toast = reactive({ visible: false, message: "", type: "success" });

// invoice est reactive (pas ref), donc pas de .value
const isLocked = computed(() => {
    if (!invoice.status) return true;
    const isCommercialDraft = invoice.status === 'draft';
    const isFiscalDraft = !invoice.fiscal_status || invoice.fiscal_status === 'draft' || invoice.fiscal_status === 'rejected';
    return !isCommercialDraft || !isFiscalDraft;
});

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
        const data = res.data.clients || res.data || [];
        clients.value = data;
        clientOptions.value = clients.value.map(c => ({
            value: String(c.id),
            text: `${c.company_name} - ${c.email} (${c.currency_symbol || '€'})`
        }));
    } catch (e) {} finally { loading.value = false; }
};

const fetchInvoiceDetails = async () => {
    loading.value = true;
    try {
        const id = route.params.id;
        const res = await axios.get(`/wp-json/my-easy-compta/v1/invoices/${id}`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        const invoiceData = res.data.data || res.data;
        Object.assign(invoice, invoiceData);
        
        // Handle Date conversion robustly
        const dateStr = invoiceData.due_date_raw || invoiceData.due_date;
        if (dateStr && typeof dateStr === 'string') {
             const dateObj = new Date(dateStr);
             if (!isNaN(dateObj.getTime())) {
                 invoice.due_date = dateObj;
             } else {
                 const parts = dateStr.split("-");
                 if(parts.length === 3) {
                      if (parts[0].length === 4) invoice.due_date = new Date(parts[0], parts[1] - 1, parts[2]);
                      else invoice.due_date = new Date(parts[2], parts[1] - 1, parts[0]);
                 }
             }
        }
        
        // Convert client_id to string for ModelSelect
        if (invoice.client_id) invoice.client_id = String(invoice.client_id);
    } catch(e) { 
        showToast("Erreur lors du chargement", "error");
    } finally { loading.value = false; }
};

const submitInvoice = async () => {
  showError.value = true;
  if (!invoice.due_date || !invoice.client_id || !invoice.status) {
    showToast("Veuillez remplir tous les champs obligatoires.", "error");
    return;
  }

  loadingBtn.value = true;
  const id = route.params.id;

  try {
     // Prepare payload. Unwrap client_id if object
     const payload = { ...invoice };
     if (typeof payload.client_id === 'object' && payload.client_id !== null) {
         payload.client_id = payload.client_id.value;
     }
     
    // Format date not needed if backend accepts full date string, but legacy did not format it in Edit.vue?
    // Edit.vue legacy: body: JSON.stringify(this.invoice).
    // if invoice.due_date is Date object, JSON.stringify makes it ISO string.
    // Backend likely handles it.

    const response = await axios.put(`/wp-json/my-easy-compta/v1/invoices/${id}`, payload, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });

    if (response.data.success) {
      showToast(response.data.message, "success");
      setTimeout(() => router.push({ name: "InvoiceViewDetail", params: { id } }), 1500);
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
        await axios.post(`/wp-json/my-easy-compta/v1/invoices/${id}/notes`, { internal_notes: invoice.internal_notes }, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        notesSaved.value = true;
        setTimeout(() => notesSaved.value = false, 2000);
    } catch (e) {}
};

const cancelAction = () => router.push("/invoices");

onMounted(async () => {
   // Fetch clients first to ensure options are ready for model-select (if needed)
   await fetchClients();
   await fetchInvoiceDetails();
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
