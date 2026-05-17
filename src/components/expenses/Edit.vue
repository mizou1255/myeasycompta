<template>
  <div>
    <!-- Toast Notification -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'alert-success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'alert-success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <!-- Modal -->
    <dialog :id="modalId" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ modalTitle }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                <X class="w-6 h-6" />
            </button>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
             <div v-for="n in 4" :key="n" class="space-y-2">
                 <div class="h-3 w-20 bg-slate-100 dark:bg-slate-800 rounded-full animate-pulse"></div>
                 <div class="h-12 w-full bg-slate-100 dark:bg-slate-800 rounded-2xl animate-pulse"></div>
             </div>
        </div>

        <form v-else @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.amount }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" v-model="editedExpense.amount" required class="kloxy-input" />
                 </div>
                 
                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.expense_date }} <span class="text-rose-500">*</span>
                    </label>
                    <VueDatePicker
                        v-model="editedExpense.expense_date"
                        :enable-time-picker="false"
                        auto-apply
                        :format="formattedDate"
                        locale="fr"
                        required
                        input-class-name="w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white"
                     />
                 </div>

                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.client }}
                    </label>
                    <div class="kloxy-select-wrapper">
                        <model-select
                           v-model="editedExpense.client_id"
                           :options="clients"
                           :placeholder="translations.select_client || 'Sélectionner un client'"
                           class="kloxy-select"
                        />
                    </div>
                 </div>

                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.category }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select v-model="editedExpense.category_id" required class="kloxy-select-native">
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                         <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>
                 </div>
            </div>

             <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.note }}</label>
                <textarea v-model="editedExpense.notes" class="kloxy-input min-h-[100px]" :placeholder="translations.note_placeholder"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                   {{ translations.cancel || 'Annuler' }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Save class="w-4 h-4" />
                   {{ translations.save }}
               </button>
            </div>
        </form>
      </div>
      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none"></form>
    </dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, toRefs } from 'vue';
import { ModelSelect } from "vue-search-select";
import VueDatePicker from "@vuepic/vue-datepicker";
import { X, CheckCircle2, AlertCircle, ChevronDown, Save } from 'lucide-vue-next';
import axios from 'axios';
import 'vue-search-select/dist/VueSearchSelect.css';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    expense: Object,
    categories: Array,
    clients: Array
});

const emit = defineEmits(['expenseUpdated']);

const { expense } = toRefs(props);
const editedExpense = ref({ ...props.expense });

watch(expense, (newVal) => {
    if (!newVal) return;
    editedExpense.value = { ...newVal };
    
    // Use date_raw for reliable parsing if available, otherwise fallback to expense_date
    const dateStr = newVal.date_raw || newVal.expense_date;
    if (dateStr && typeof dateStr === 'string') {
        const dateObj = new Date(dateStr);
        if (!isNaN(dateObj.getTime())) {
            editedExpense.value.expense_date = dateObj;
        } else {
            // Fallback for DD-MM-YYYY if date_raw failed
            const parts = dateStr.split("-");
            if(parts.length === 3) {
                 // Check if first part is year or day
                 if (parts[0].length === 4) editedExpense.value.expense_date = new Date(parts[0], parts[1] - 1, parts[2]);
                 else editedExpense.value.expense_date = new Date(parts[2], parts[1] - 1, parts[0]);
            }
        }
    }
}, { deep: true, immediate: true });

const openModal = (expenseData) => {
    editedExpense.value = { ...expenseData };
    
    // Convert IDs to strings for robust matching in ModelSelect/Select components
    if (expenseData.client_id) editedExpense.value.client_id = String(expenseData.client_id);
    if (expenseData.category_id) editedExpense.value.category_id = String(expenseData.category_id);

    // Handling date parsing from date_raw or formatted date
    const dateStr = expenseData.date_raw || expenseData.expense_date;
    if (dateStr && typeof dateStr === 'string') {
        const dateObj = new Date(dateStr);
        if (!isNaN(dateObj.getTime())) {
            editedExpense.value.expense_date = dateObj;
        } else {
            const parts = dateStr.split("-");
            if (parts.length === 3) {
                if (parts[0].length === 4) editedExpense.value.expense_date = new Date(parts[0], parts[1] - 1, parts[2]);
                else editedExpense.value.expense_date = new Date(parts[2], parts[1] - 1, parts[0]);
            }
        }
    }

    if (expenseData.total_amount && !expenseData.amount) {
        editedExpense.value.amount = expenseData.total_amount;
    }
    
    const modal = document.getElementById(props.modalId);
    if(modal) modal.showModal();
};

const closeModal = () => {
    const modal = document.getElementById(props.modalId);
    if(modal) modal.close();
};

defineExpose({ openModal, closeModal });

const loadingBtn = ref(false);
const toast = reactive({ visible: false, message: "", type: "alert-success" });
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const formattedDate = (date) => {
    if (!date) return "";
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
};

const showToast = (message, type) => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};



const submitForm = async () => {
    loadingBtn.value = true;
    try {
        // Date formatting if needed? axios usually handles Date objects as ISO strings.
        const payload = { ...editedExpense.value };
        if(payload.client_id && typeof payload.client_id === 'object') payload.client_id = payload.client_id.value;
        if(payload.expense_date instanceof Date) {
            // "YYYY-MM-DD"
             const y = payload.expense_date.getFullYear();
             const m = String(payload.expense_date.getMonth() + 1).padStart(2,'0');
             const d = String(payload.expense_date.getDate()).padStart(2,'0');
             payload.expense_date = `${y}-${m}-${d}`;
        }
        
        const res = await axios.put(`/wp-json/my-easy-compta/v1/expenses/${payload.id}`, payload, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });

        if (res.data.success || res.status === 200) {
            showToast(res.data.message || 'Updated', "alert-success");
            emit('expenseUpdated');
            closeModal();
        } else {
             showToast(res.data.message || "Error", "alert-error");
        }
    } catch (e) {
        showToast("Error updating expense", "alert-error");
    } finally { loadingBtn.value = false; }
};
</script>

<style>
.kloxy-input {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
.kloxy-select-native {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer;
}

/* Custom overrides for vue-search-select to match Kloxy */
.kloxy-select-wrapper .ui.selection.dropdown {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm min-h-[52px] flex items-center shadow-none !important;
}
.kloxy-select-wrapper .ui.selection.dropdown .text { @apply text-slate-900 dark:text-white !important; }
.kloxy-select-wrapper .ui.selection.dropdown .menu { @apply border-none rounded-xl shadow-xl mt-2 overflow-hidden !important; }
.kloxy-select-wrapper .ui.selection.dropdown .menu .item { @apply p-4 hover:bg-slate-50 dark:hover:bg-slate-800 !important; }
</style>