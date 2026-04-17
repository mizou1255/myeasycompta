<template>
  <dialog :open="showModal" :class="['fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 w-full h-full border-none m-0 max-w-none max-h-none', showModal ? 'block' : 'hidden']">
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-full max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative max-h-[90vh] overflow-y-auto">
      
      <!-- Toast Notification -->
      <div v-if="toast.visible" class="fixed bottom-8 right-8 z-[9999] animate-in fade-in slide-in-from-bottom-8 duration-300">
        <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'alert-success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
          <component :is="toast.type === 'alert-success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
          <span class="font-bold text-sm">{{ toast.message }}</span>
          <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
        </div>
      </div>

      <!-- Header -->
      <div class="flex items-center justify-between mb-8 sticky top-0 bg-white dark:bg-slate-900 z-10 pb-4 border-b border-slate-100 dark:border-slate-800">
          <h3 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
              <Pencil class="w-6 h-6 text-purple-600" />
              {{ modalTitle }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
              <X class="w-6 h-6" />
          </button>
      </div>

      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
           <div v-for="n in 8" :key="n" class="space-y-2">
               <div class="h-3 w-20 bg-slate-100 dark:bg-slate-800 rounded-full animate-pulse"></div>
               <div class="h-12 w-full bg-slate-100 dark:bg-slate-800 rounded-2xl animate-pulse"></div>
           </div>
      </div>

      <form v-else @submit.prevent="submitForm" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div v-for="(field, key) in fields" :key="key" class="space-y-2">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                      {{ field.label }}
                  </label>
                  <input :type="field.type || 'text'" v-model="editedClient[key]" :placeholder="field.label" class="kloxy-input" />
               </div>

               <!-- Currency -->
               <div class="space-y-2">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                      {{ translations.currency }}
                  </label>
                  <div class="relative">
                      <select v-model="editedClient.currency_id" class="kloxy-select-native">
                          <option v-for="opt in currencyOptions" :key="opt.id" :value="opt.id">
                              {{ opt.name }} - {{ opt.code }} ({{ opt.symbol }})
                          </option>
                      </select>
                       <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
               </div>
          </div>

           <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.note }}</label>
              <textarea v-model="editedClient.note" class="kloxy-input min-h-[100px]" :placeholder="translations.note"></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 sticky bottom-0 bg-white dark:bg-slate-900 z-10 py-4">
             <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                 {{ translations.cancel || 'Annuler' }}
             </button>
             <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                 <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                 <Check v-else class="w-4 h-4" />
                 {{ translations.save }}
             </button>
          </div>
      </form>
    </div>
  </dialog>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { X, CheckCircle2, AlertCircle, ChevronDown, Pencil, Check } from 'lucide-vue-next';

const props = defineProps({
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    client: Object
});

const emit = defineEmits(['close', 'clientEdited']);

const editedClient = ref({ ...props.client });
const currencyOptions = ref([]);
const loadingBtn = ref(false);
const toast = reactive({ visible: false, message: "", type: "alert-success" });

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const fields = computed(() => ({
    siren_number: { label: translations.value.siren },
    tax_number: { label: translations.value.tax_number },
    company_name: { label: translations.value.company_name },
    manager_name: { label: translations.value.manager_name },
    email: { label: translations.value.email },
    phone: { label: translations.value.phone, type: "tel" },
    mobile_phone: { label: translations.value.mobile, type: "tel" },
    website: { label: translations.value.website, type: "url" },
    address: { label: translations.value.address },
    city: { label: translations.value.city },
    postal_code: { label: translations.value.postal_code },
    country: { label: translations.value.country },
}));

watch(() => props.client, (newVal) => {
    editedClient.value = { ...newVal };
}, { deep: true, immediate: true });

const showToast = (message, type) => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const closeModal = () => emit('close');

const fetchOptions = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/options`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if(res.ok) {
            const data = await res.json();
            currencyOptions.value = data.currency_options;
        }
    } catch (e) {}
};

const submitForm = async () => {
    loadingBtn.value = true;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/clients/${editedClient.value.id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": window.myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(editedClient.value),
        });
        if (res.ok) {
             const data = await res.json();
             showToast(data.message, "alert-success");
             emit('clientEdited');
             setTimeout(closeModal, 500);
        } else {
             showToast("Error update", "error");
        }
    } catch (e) {
        showToast("Server Error", "error");
    } finally { loadingBtn.value = false; }
};

onMounted(fetchOptions);
</script>

<style scoped>
.kloxy-input {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
.kloxy-select-native {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer;
}
</style>
