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
    <dialog id="modal_expenses" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ translations.add }}</h3>
            <div class="flex items-center gap-2">
                <!-- OCR scan button (only if addon active) -->
                <button
                    v-if="ocrAddonActive"
                    type="button"
                    @click="triggerOcrInput"
                    :disabled="ocrLoading"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl font-black text-xs uppercase tracking-widest bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors disabled:opacity-50"
                    :title="'Scanner un reçu (OCR)'"
                >
                    <span v-if="ocrLoading" class="w-4 h-4 border-2 border-violet-400/30 border-t-violet-500 rounded-full animate-spin"></span>
                    <ScanLine v-else class="w-4 h-4" />
                    <span class="hidden sm:inline">Scanner</span>
                </button>
                <input ref="ocrFileInput" type="file" accept="image/*,.pdf" class="hidden" @change="handleOcrFile" />
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                    <X class="w-6 h-6" />
                </button>
            </div>
        </div>

        <!-- OCR result banner -->
        <div v-if="ocrResult" class="flex items-start gap-3 p-4 bg-violet-50 dark:bg-violet-900/20 rounded-2xl border border-violet-100 dark:border-violet-800 mb-2">
            <ScanLine class="w-4 h-4 text-violet-500 mt-0.5 shrink-0" />
            <div class="flex-1 text-xs font-bold text-violet-700 dark:text-violet-300">
                Reçu scanné — champs pré-remplis. Vérifiez et corrigez si nécessaire.
            </div>
            <button type="button" @click="ocrResult = null" class="text-violet-400 hover:text-violet-600"><X class="w-3.5 h-3.5" /></button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.amount }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" v-model="formData.amount" required class="kloxy-input" />
                 </div>
                 
                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.expense_date }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" v-model="formData.expense_date" required class="kloxy-input" />
                 </div>

                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.client }}
                    </label>
                    <div class="relative">
                        <select v-model="formData.client_id" class="kloxy-select">
                            <option value="">{{ translations.select_client }}</option>
                            <option v-for="client in options.clients" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                        </select>
                        <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>
                 </div>

                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.category }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select v-model="formData.category_id" required class="kloxy-select">
                            <option value="" disabled>{{ translations.select_category || 'Sélectionner' }}</option>
                            <option v-for="cat in options.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                         <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>
                 </div>
            </div>

            <!-- Attachment -->
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.attached_file }}</label>
                <div class="relative group">
                    <input type="file" ref="attachmentInput" @change="handleFileChange" class="hidden" id="file-upload" />
                    <label for="file-upload" class="flex items-center justify-center w-full p-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-slate-800 transition-all group">
                         <div class="flex flex-col items-center gap-2 text-slate-400 group-hover:text-purple-600 transition-colors">
                             <UploadCloud class="w-8 h-8" />
                             <span class="text-xs font-bold">{{ fileName || translations.choose_file || 'Choisir un fichier' }}</span>
                         </div>
                    </label>
                </div>
            </div>

             <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.note }}</label>
                <textarea v-model="formData.note" class="kloxy-input min-h-[100px]" :placeholder="translations.note_placeholder"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                   {{ translations.cancel || 'Annuler' }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Plus class="w-4 h-4" />
                   {{ translations.add }}
               </button>
            </div>
        </form>
      </div>
      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none"></form>
    </dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { X, CheckCircle2, AlertCircle, ChevronDown, UploadCloud, Plus, ScanLine } from 'lucide-vue-next';

const emit = defineEmits(['expenseAdded']);

// State
const formData = reactive({
    amount: "",
    expense_date: "",
    client_id: "",
    category_id: "",
    note: "",
});
const attachmentFile = ref(null);
const fileName = ref('');
const attachmentInput = ref(null);
const ocrFileInput = ref(null);
const ocrLoading = ref(false);
const ocrResult = ref(null);
const options = reactive({ clients: [], categories: [] });
const loadingBtn = ref(false);
const toast = reactive({ visible: false, message: "", type: "alert-success" });

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const ocrAddonActive = computed(() => !!window.myEasyComptaAdmin?.ocrAddonActive);

// Methods
const showToast = (message, type) => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const handleFileChange = (e) => {
   const file = e.target.files[0];
   if(file) {
       attachmentFile.value = file;
       fileName.value = file.name;
   }
};

const triggerOcrInput = () => ocrFileInput.value?.click();

const handleOcrFile = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    ocrLoading.value = true;
    ocrResult.value = null;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const res = await fetch('/wp-json/my-easy-compta/v1/ocr/scan', {
            method: 'POST',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: fd,
        });
        const data = await res.json();
        if (res.ok && data.success && data.extracted) {
            const { amount, date, vendor } = data.extracted;
            if (amount)  formData.amount       = amount;
            if (date)    formData.expense_date = date;
            if (vendor)  formData.note         = vendor + (formData.note ? '\n' + formData.note : '');
            ocrResult.value = data.extracted;
            // Also pre-attach the scanned file
            attachmentFile.value = file;
            fileName.value = file.name;
        } else {
            showToast(data.message || 'OCR : aucun résultat', 'alert-error');
        }
    } catch {
        showToast('Erreur lors du scan', 'alert-error');
    } finally {
        ocrLoading.value = false;
        if (ocrFileInput.value) ocrFileInput.value.value = '';
    }
};

const fetchOptions = async () => {
    try {
        const [resClients, resCats] = await Promise.all([
             fetch("/wp-json/my-easy-compta/v1/expenses/clients", { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }}),
             fetch("/wp-json/my-easy-compta/v1/expenses/categories", { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }})
        ]);
        if(resClients.ok) options.clients = await resClients.json();
        if(resCats.ok) options.categories = await resCats.json();
    } catch (e) {}
};

const openModal = () => {
    const modal = document.getElementById("modal_expenses");
    if(modal) modal.showModal();
};

const closeModal = () => {
    const modal = document.getElementById("modal_expenses");
    if(modal) modal.close();
};

defineExpose({ openModal, closeModal });

const resetForm = () => {
    Object.assign(formData, { amount: "", expense_date: "", client_id: "", category_id: "", note: "" });
    attachmentFile.value = null;
    fileName.value = '';
    ocrResult.value = null;
    if(attachmentInput.value) attachmentInput.value.value = '';
};

const submitForm = async () => {
    loadingBtn.value = true;
    const data = new FormData();
    Object.keys(formData).forEach(key => data.append(key, formData[key]));
    if(attachmentFile.value) data.append("attachment", attachmentFile.value);
    
    try {
        const res = await fetch("/wp-json/my-easy-compta/v1/expenses", {
            method: 'POST',
            body: data,
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const result = await res.json();
        if(res.ok) {
            showToast(result.message, "alert-success");
            emit('expenseAdded');
            resetForm();
            closeModal();
        } else {
             showToast(result.message || "Erreur", "alert-error");
        }
    } catch(e) { 
        showToast("Erreur serveur", "alert-error");
    } finally { loadingBtn.value = false; }
};

onMounted(fetchOptions);
</script>

<style scoped>
.kloxy-input {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
.kloxy-select {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer;
}
</style>